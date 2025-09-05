"use client"

import { Area, AreaChart, CartesianGrid, XAxis, YAxis, ResponsiveContainer } from "recharts"


import {
  ChartConfig,
  ChartContainer,
} from "@/components/ui/chart"

export const description = "A linear area chart"

// const chartData = [
//   { month: "January", desktop: 35 },
//   { month: "February", desktop: 80 },
//   { month: "March", desktop: 50 },
//   { month: "April", desktop: 10 },
//   { month: "May", desktop: 40 },
//   { month: "June", desktop: 40 },
// ]

const chartConfig = {
  desktop: {
    label: "Desktop",
    color: "var(--chart-1)",
  },
  month: {
    label: "Month",
    color: "var(--chart-2)",
  },
} satisfies ChartConfig

interface ChartDataItem {
  month: string;
  desktop: number;
}

interface SimpleAreaChartProps {
  DashboardData: ChartDataItem[];
}

export function SimpleAreaChart({ DashboardData }: SimpleAreaChartProps) {
  return (
    <ChartContainer config={chartConfig} className="h-[300px] focus:outline-none">
        <ResponsiveContainer width="100%" height="100%">
          <AreaChart
            accessibilityLayer
            data={DashboardData}
            margin={{
              left: -8,
              right: 12,
              top: 12,
              bottom: 12,
            }}
          >
            <defs>
              <linearGradient id="areaGradient" x1="261.752" y1="0.0579376" x2="261.752" y2="207.71" gradientUnits="userSpaceOnUse">
                <stop stopColor="#6366F1" />
                <stop offset="1" stopColor="white" stopOpacity="0" />
              </linearGradient>
            </defs>

            <CartesianGrid vertical={false} />
            <XAxis
              dataKey="month"
              tickLine={false}
              axisLine={false}
              tickMargin={8}
              tickFormatter={(value) => value.slice(0, 3)}
            />
            <YAxis
              tickLine={false}
              axisLine={false}
              tickMargin={6}
              tickCount={6}
              width={35}
            />
            {/* <ChartTooltip
              cursor={false}
              content={<ChartTooltipContent indicator="dot" hideLabel />}
            /> */}

            <Area
              dataKey="desktop"
              type="linear"
              fill="url(#areaGradient)"
              stroke="#6366F1"
              strokeWidth="2"
              stackId="a"
            />
          </AreaChart>
        </ResponsiveContainer>
    </ChartContainer>
  )
}
