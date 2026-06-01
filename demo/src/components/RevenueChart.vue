<script setup lang="ts">
import { ref } from 'vue';
import { fakeMonthlyRevenue } from '../data/fake';

const data = fakeMonthlyRevenue;
const maxVal = Math.max(...data.map(d => d.amount));

const chartH = 90;
const barW = 36;
const gap = 14;
const paddingLeft = 46;
const paddingTop = 8;
const paddingBottom = 24;
const paddingRight = 8;
const totalW = paddingLeft + data.length * (barW + gap) - gap + paddingRight;
const totalH = paddingTop + chartH + paddingBottom;

const hovered = ref<number | null>(null);

function barH(amount: number) {
    if (amount === 0) return 3;
    return Math.round((amount / maxVal) * chartH);
}
function barX(i: number) { return paddingLeft + i * (barW + gap); }
function barY(amount: number) { return paddingTop + chartH - barH(amount); }

function gridY(ratio: number) { return paddingTop + chartH - ratio * chartH; }
function gridLabel(ratio: number) {
    const v = Math.round(maxVal * ratio);
    return v >= 1000 ? `${(v / 1000).toFixed(0)}k` : String(v);
}

function tooltipX(i: number) {
    const cx = barX(i) + barW / 2;
    return Math.max(24, Math.min(cx - 24, totalW - 48));
}
function tooltipY(amount: number) {
    return Math.max(0, barY(amount) - 22);
}
</script>

<template>
    <div class="liquid-glass p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-[hsl(var(--text-main))]">Chiffre d'affaires mensuel</h2>
            <span class="text-xs text-[hsl(var(--text-muted))]">2026</span>
        </div>
        <svg
            :viewBox="`0 0 ${totalW} ${totalH}`"
            :height="totalH"
            class="w-full overflow-visible"
        >
            <!-- Grid lines + Y labels -->
            <g v-for="ratio in [0, 0.25, 0.5, 0.75, 1]" :key="ratio">
                <line
                    :x1="paddingLeft - 6" :y1="gridY(ratio)"
                    :x2="totalW - paddingRight" :y2="gridY(ratio)"
                    class="chart-grid"
                    stroke-width="1"
                    stroke-dasharray="3 3"
                />
                <text
                    :x="paddingLeft - 10" :y="gridY(ratio) + 4"
                    font-size="9" text-anchor="end"
                    class="chart-label"
                >{{ gridLabel(ratio) }}</text>
            </g>

            <!-- Bars -->
            <g
                v-for="(d, i) in data"
                :key="d.month"
                class="cursor-pointer"
                @mouseenter="hovered = i"
                @mouseleave="hovered = null"
            >
                <rect
                    :x="barX(i)" :y="barY(d.amount)"
                    :width="barW" :height="barH(d.amount)"
                    rx="5"
                    :class="hovered === i ? 'chart-bar-active' : 'chart-bar'"
                />
                <!-- Tooltip -->
                <g v-if="hovered === i">
                    <rect
                        :x="tooltipX(i)" :y="tooltipY(d.amount)"
                        width="48" height="17" rx="4"
                        class="chart-tooltip-bg"
                    />
                    <text
                        :x="tooltipX(i) + 24" :y="tooltipY(d.amount) + 11"
                        font-size="9" text-anchor="middle" font-weight="600"
                        class="chart-value"
                    >{{ d.amount.toLocaleString('fr-FR') }} €</text>
                </g>
                <!-- Month label -->
                <text
                    :x="barX(i) + barW / 2"
                    :y="totalH - 4"
                    font-size="10" text-anchor="middle"
                    class="chart-label"
                >{{ d.month }}</text>
            </g>
        </svg>
    </div>
</template>
