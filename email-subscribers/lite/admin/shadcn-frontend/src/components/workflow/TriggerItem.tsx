import React from 'react'
import { GripVertical } from 'lucide-react'

interface TriggerItemProps {
  icon: React.ReactNode
  title: string
  category: string
  isDraggable?: boolean
  onDragStart?: (e: React.DragEvent, trigger: any) => void
  onDragEnd?: () => void
  onClick?: () => void
}

export function TriggerItem({ icon, title, category, isDraggable = true, onDragStart, onDragEnd, onClick }: TriggerItemProps) {
  const triggerData = { icon, title, category }
  
  return (
    <div 
      className="bg-white relative rounded-lg w-full hover:shadow-md transition-shadow cursor-pointer"
      draggable={isDraggable}
      onDragStart={(e) => onDragStart?.(e, triggerData)}
      onDragEnd={onDragEnd}
      onClick={onClick}
    >
      <div className="absolute border border-neutral-200 border-solid inset-0 pointer-events-none rounded-lg shadow-[0px_4px_6px_0px_rgba(0,0,0,0.09)]" />
      <div className="flex flex-row items-center relative w-full">
        <div className="box-border flex flex-row gap-1.5 items-center justify-start px-3 py-2.5 w-full">
          <div className="overflow-hidden relative shrink-0 size-4">
            {icon}
          </div>
          <div className="basis-0 font-medium grow leading-5 min-h-px min-w-px not-italic relative shrink-0 text-[14px] text-left text-neutral-950">
            {title}
          </div>
          {isDraggable && (
            <div className="opacity-40 overflow-hidden relative shrink-0 size-4">
              <GripVertical className="size-4" />
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
