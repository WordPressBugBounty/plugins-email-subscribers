"use client"

import * as React from "react"
import { cn } from "@/lib/utils"

// Chart component for area charts
interface ChartConfig {
  [key: string]: {
    label: string
    color: string
  }
}

interface ChartContainerProps extends React.HTMLAttributes<HTMLDivElement> {
  config: ChartConfig
  children: React.ReactNode
}

const ChartContainer = React.forwardRef<HTMLDivElement, ChartContainerProps>(
  ({ className, config, children, ...props }, ref) => {
    return (
      <div
        ref={ref}
        className={cn("w-full h-full", className)}
        style={
          {
            "--chart-1": config.data?.color || "#6366f1",
            "--chart-2": config.data2?.color || "#8b5cf6",
            "--chart-3": config.data3?.color || "#06b6d4",
            "--chart-4": config.data4?.color || "#84cc16",
            "--chart-5": config.data5?.color || "#f59e0b",
          } as React.CSSProperties
        }
        {...props}
      >
        {children}
      </div>
    )
  }
)
ChartContainer.displayName = "ChartContainer"

export { ChartContainer, type ChartConfig }
