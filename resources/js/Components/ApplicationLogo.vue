<template>
    <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Background Circle -->
        <circle cx="100" cy="100" r="90" fill="url(#gradient)" opacity="0.1"/>

        <!-- Modern Building/Office Icon -->
        <g transform="translate(50, 45)">
            <!-- Main building structure -->
            <rect x="15" y="20" width="70" height="90" rx="4" fill="currentColor" opacity="0.9"/>

            <!-- Windows -->
            <rect x="25" y="30" width="15" height="15" rx="2" fill="white" opacity="0.3"/>
            <rect x="45" y="30" width="15" height="15" rx="2" fill="white" opacity="0.3"/>
            <rect x="65" y="30" width="15" height="15" rx="2" fill="white" opacity="0.3"/>

            <rect x="25" y="50" width="15" height="15" rx="2" fill="white" opacity="0.3"/>
            <rect x="45" y="50" width="15" height="15" rx="2" fill="white" opacity="0.3"/>
            <rect x="65" y="50" width="15" height="15" rx="2" fill="white" opacity="0.3"/>

            <rect x="25" y="70" width="15" height="15" rx="2" fill="white" opacity="0.3"/>
            <rect x="45" y="70" width="15" height="15" rx="2" fill="white" opacity="0.3"/>
            <rect x="65" y="70" width="15" height="15" rx="2" fill="white" opacity="0.3"/>

            <!-- Entrance -->
            <rect x="40" y="95" width="20" height="15" rx="2" fill="white" opacity="0.5"/>

            <!-- Accent top -->
            <path d="M15 20 L50 5 L85 20 Z" fill="currentColor" opacity="0.8"/>
        </g>

        <!-- Gradient Definition -->
        <defs>
            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:currentColor;stop-opacity:1" />
                <stop offset="100%" style="stop-color:currentColor;stop-opacity:0.5" />
            </linearGradient>
        </defs>
    </svg>
</template>