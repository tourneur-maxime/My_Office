import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    darkMode: 'class',

    theme: {
        container: {
            center: true,
            padding: "2rem",
            screens: {
                "2xl": "1400px",
            },
        },
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                border: "hsl(var(--border) / <alpha-value>)",
                input: "hsl(var(--border) / <alpha-value>)", // Use border color for inputs
                background: "hsl(var(--bg-base) / <alpha-value>)",
                foreground: "hsl(var(--text-main) / <alpha-value>)",
                primary: {
                    DEFAULT: "hsl(var(--primary) / <alpha-value>)",
                    foreground: "#ffffff",
                },
                secondary: {
                    DEFAULT: "hsl(var(--bg-surface) / <alpha-value>)", // Surface color for secondary
                    foreground: "hsl(var(--text-main) / <alpha-value>)",
                },
                muted: {
                    DEFAULT: "hsl(var(--bg-surface) / <alpha-value>)", // Muted often maps to surface/secondary bg
                    foreground: "hsl(var(--text-muted) / <alpha-value>)",
                },
                accent: {
                    DEFAULT: "hsl(var(--primary) / 0.1)", // Light tint of primary
                    foreground: "hsl(var(--primary) / <alpha-value>)",
                },
                card: {
                    DEFAULT: "hsl(var(--bg-surface) / 0.6)", // Glassy
                    foreground: "hsl(var(--text-main) / <alpha-value>)",
                },
                // Apple-inspired defaults kept for backward compat if needed, mapped to vars where possible
                apple: {
                    gray: "hsl(var(--bg-base) / <alpha-value>)",
                    blue: "hsl(var(--primary) / <alpha-value>)",
                    black: "hsl(var(--text-main) / <alpha-value>)",
                    secondary: "hsl(var(--text-muted) / <alpha-value>)",
                    dark: {
                        bg: "hsl(var(--bg-base) / <alpha-value>)",
                        card: "hsl(var(--bg-surface) / <alpha-value>)",
                        text: "hsl(var(--text-main) / <alpha-value>)",
                        secondary: "hsl(var(--text-muted) / <alpha-value>)",
                        border: "hsl(var(--border) / <alpha-value>)",
                    }
                },
            },
            borderRadius: {
                lg: "var(--radius)",
                md: "calc(var(--radius) - 2px)",
                sm: "calc(var(--radius) - 4px)",
            },
            boxShadow: {
                'liquid': '0 25px 50px -12px hsl(var(--primary) / 0.1), 0 0 0 1px hsl(var(--border) / 0.2)',
                'glass': '0 8px 32px 0 hsl(var(--primary) / 0.05)',
                'glass-hover': '0 8px 32px 0 hsl(var(--primary) / 0.15)',
            },
        },
    },

    plugins: [forms, require('daisyui')],

    daisyui: {
        themes: [
            {
                myoffice: {
                    "primary": "#0071E3",
                    "secondary": "#06b6d4",
                    "accent": "#f59e0b",
                    "neutral": "#3d4451",
                    "base-100": "#ffffff",
                    "info": "#3abff8",
                    "success": "#36d399",
                    "warning": "#fbbd23",
                    "error": "#f87272",
                },
            },
            "light",
            "dark",
        ],
    },
};