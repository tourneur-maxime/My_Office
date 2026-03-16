import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useThemeStore = defineStore('theme', () => {
    const preferences = ref({
        mode: 'system',
        primary_color: '#0071E3',
        gray_shade: 'slate',
        radius: 'lg',
        card_border_style: 'subtle', // subtle, medium, strong
    });

    const initTheme = (userPreferences) => {
        if (userPreferences) {
            preferences.value = { ...preferences.value, ...userPreferences };
        } else if (typeof window !== 'undefined' && localStorage.getItem('theme_mode')) {
            // For guest users, read from localStorage
            preferences.value.mode = localStorage.getItem('theme_mode');
        }

        // Apply theme immediately before rendering
        applyTheme();

        // Listen for system theme changes
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addEventListener('change', () => {
                if (preferences.value.mode === 'system') {
                    applyTheme();
                }
            });
        }
    };

    const updatePreferences = (newPrefs) => {
        preferences.value = { ...preferences.value, ...newPrefs };
        
        // Persist mode to localStorage for guest continuity
        if (newPrefs.mode && typeof window !== 'undefined') {
            localStorage.setItem('theme_mode', newPrefs.mode);
        }

        applyTheme();
    };

    const applyTheme = () => {
        const root = document.documentElement;

        // 1. Handle Dark Mode
        const isDark = 
            preferences.value.mode === 'dark' ||
            (preferences.value.mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        if (isDark) {
            root.classList.add('dark');
            root.setAttribute('data-theme', 'dark');
        } else {
            root.classList.remove('dark');
            root.setAttribute('data-theme', 'light');
        }

        // 2. Apply Radius
        const radii = {
            none: '0px',
            sm: '0.125rem',
            md: '0.375rem',
            lg: '0.5rem',
            xl: '0.75rem',
            full: '9999px',
        };
        root.style.setProperty('--radius', radii[preferences.value.radius]);

        // 3. Apply Primary Color (HSL conversion for Tailwind alpha support)
        const primaryHsl = hexToHsl(preferences.value.primary_color);
        root.style.setProperty('--primary', `${primaryHsl.h} ${primaryHsl.s}% ${primaryHsl.l}%`);

        // 4. Apply Grays (Backgrounds)
        const grayPalettes = {
            slate: {
                50: '210 40% 98%',
                100: '210 40% 96.1%',
                200: '214.3 31.8% 91.4%',
                300: '212.7 26.8% 83.9%',
                400: '215 20.2% 65.1%',
                500: '215.4 16.3% 46.9%',
                600: '215.3 19.3% 34.5%',
                700: '215.3 25% 26.7%',
                800: '217.2 32.6% 17.5%',
                900: '222.2 47.4% 11.2%',
                950: '222.2 84% 4.9%',
            },
            zinc: {
                50: '0 0% 98%',
                100: '240 4.8% 95.9%',
                200: '240 5.9% 90%',
                300: '240 4.9% 83.9%',
                400: '240 5% 64.9%',
                500: '240 3.8% 46.1%',
                600: '240 5.2% 33.9%',
                700: '240 5.3% 26.1%',
                800: '240 3.7% 15.9%',
                900: '240 5.9% 10%',
                950: '240 10% 3.9%',
            },
            neutral: {
                50: '0 0% 98%',
                100: '0 0% 96.1%',
                200: '0 0% 92%',
                300: '0 0% 87%',
                400: '0 0% 66%',
                500: '0 0% 45%',
                600: '0 0% 32%',
                700: '0 0% 25%',
                800: '0 0% 15%',
                900: '0 0% 9%',
                950: '0 0% 4%',
            },
             stone: {
                50: '60 9.1% 97.8%',
                100: '60 4.8% 95.9%',
                200: '20 5.9% 90%',
                300: '24 5.7% 82.9%',
                400: '24 5.4% 63.9%',
                500: '25 5.3% 44.7%',
                600: '33.3 5.5% 32.4%',
                700: '30 6.3% 25.1%',
                800: '12 5.8% 15.1%',
                900: '24 9.8% 10%',
                950: '0 0% 6%', // Custom dark
            },
             gray: {
                50: '210 20% 98%', // Cool Gray
                100: '220 14% 96%',
                200: '220 13% 91%',
                300: '216 12% 84%',
                400: '218 11% 65%',
                500: '220 9% 46%',
                600: '215 14% 34%',
                700: '217 19% 27%',
                800: '215 28% 17%',
                900: '221 39% 11%',
                950: '224 71% 4%',
            }
        };

        const palette = grayPalettes[preferences.value.gray_shade] || grayPalettes.slate;

        // Map palette to generic variables with improved contrast
        // Light Mode - Use palette for backgrounds
        root.style.setProperty('--bg-base-light', palette[50]); // Lightest from palette
        root.style.setProperty('--bg-surface-light', palette[100]); // Slightly darker for cards
        root.style.setProperty('--bg-elevated-light', '0 0% 100%'); // Pure white for elevated surfaces

        // Dark Mode - Use palette for backgrounds
        root.style.setProperty('--bg-base-dark', palette[950]); // Darkest from palette
        root.style.setProperty('--bg-surface-dark', palette[900]); // Slightly lighter surface
        root.style.setProperty('--bg-elevated-dark', palette[800]); // Elevated surfaces

        // Text Colors - Use palette
        root.style.setProperty('--text-main-light', palette[900]);
        root.style.setProperty('--text-muted-light', palette[500]);

        root.style.setProperty('--text-main-dark', palette[50]); // Light from palette
        root.style.setProperty('--text-muted-dark', palette[400]);

        // Borders - Use palette
        root.style.setProperty('--border-light', palette[200]);
        root.style.setProperty('--border-strong-light', palette[300]);

        root.style.setProperty('--border-dark', palette[800]);
        root.style.setProperty('--border-strong-dark', palette[700]);

        // Card borders based on style preference
        const borderStyles = {
            subtle: { light: palette[200], dark: palette[800] },
            medium: { light: palette[300], dark: palette[700] },
            strong: { light: palette[400], dark: palette[600] },
        };
        const borderStyle = borderStyles[preferences.value.card_border_style] || borderStyles.subtle;
        root.style.setProperty('--card-border-light', borderStyle.light);
        root.style.setProperty('--card-border-dark', borderStyle.dark);

        // Apply mode-specific values to generic variables
        if (isDark) {
            root.style.setProperty('--bg-base', 'var(--bg-base-dark)');
            root.style.setProperty('--bg-surface', 'var(--bg-surface-dark)');
            root.style.setProperty('--bg-elevated', 'var(--bg-elevated-dark)');
            root.style.setProperty('--text-main', 'var(--text-main-dark)');
            root.style.setProperty('--text-muted', 'var(--text-muted-dark)');
            root.style.setProperty('--border', 'var(--border-dark)');
            root.style.setProperty('--border-strong', 'var(--border-strong-dark)');
            root.style.setProperty('--card-border', 'var(--card-border-dark)');
        } else {
            root.style.setProperty('--bg-base', 'var(--bg-base-light)');
            root.style.setProperty('--bg-surface', 'var(--bg-surface-light)');
            root.style.setProperty('--bg-elevated', 'var(--bg-elevated-light)');
            root.style.setProperty('--text-main', 'var(--text-main-light)');
            root.style.setProperty('--text-muted', 'var(--text-muted-light)');
            root.style.setProperty('--border', 'var(--border-light)');
            root.style.setProperty('--border-strong', 'var(--border-strong-light)');
            root.style.setProperty('--card-border', 'var(--card-border-light)');
        }
    };

    const hexToHsl = (hex) => {
        let r = 0, g = 0, b = 0;
        if (hex.length === 4) {
            r = "0x" + hex[1] + hex[1];
            g = "0x" + hex[2] + hex[2];
            b = "0x" + hex[3] + hex[3];
        } else if (hex.length === 7) {
            r = "0x" + hex[1] + hex[2];
            g = "0x" + hex[3] + hex[4];
            b = "0x" + hex[5] + hex[6];
        }
        r /= 255;
        g /= 255;
        b /= 255;
        let cmin = Math.min(r,g,b), cmax = Math.max(r,g,b), delta = cmax - cmin, h = 0, s = 0, l = 0;

        if (delta === 0) h = 0;
        else if (cmax === r) h = ((g - b) / delta) % 6;
        else if (cmax === g) h = (b - r) / delta + 2;
        else h = (r - g) / delta + 4;

        h = Math.round(h * 60);
        if (h < 0) h += 360;
        l = (cmax + cmin) / 2;
        s = delta === 0 ? 0 : delta / (1 - Math.abs(2 * l - 1));
        s = +(s * 100).toFixed(1);
        l = +(l * 100).toFixed(1);

        return { h, s, l };
    };

    return {
        preferences,
        initTheme,
        updatePreferences
    };
});
