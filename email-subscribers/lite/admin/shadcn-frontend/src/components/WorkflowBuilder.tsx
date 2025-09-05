import * as React from "react"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Card, CardContent } from "@/components/ui/card"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Separator } from "@/components/ui/separator"
import { 
  UserPlus, 
  UserMinus, 
  UserCheck, 
  UserCog, 
  Rss, 
  Ban, 
  UserX, 
  Mail, 
  MailX, 
  MailCheck,
  MessageSquare, 
  Package, 
  PackagePlus, 
  DollarSign, 
  Star, 
  ShoppingCart, 
  ChevronDown, 
  GripVertical, 
  MoreVertical,
  LogOut,
  ListChecks,
  PenTool,
  Settings,
  Search,
  Clock,
  Filter,
  Target,
  Zap,
  Phone,
  MessageCircle,
  Edit,
  Copy,
  Trash2
} from "lucide-react"

interface TriggerItemProps {
  icon: React.ReactNode
  title: string
  category: string
  isDraggable?: boolean
  onDragStart?: (e: React.DragEvent, trigger: any) => void
  onDragEnd?: () => void
  onClick?: () => void
}

function TriggerItem({ icon, title, category, isDraggable = true, onDragStart, onDragEnd, onClick }: TriggerItemProps) {
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

interface WorkflowNodeProps {
  id?: string
  title: string
  icon: React.ReactNode
  backgroundColor: string
  details?: Array<{ label: string; value: string }>
  children?: React.ReactNode
  onEdit?: (node: { id: string; type: string; title: string; fields: any }) => void
  onDelete?: (id: string) => void
  fields?: any
  type?: string
}

function WorkflowNode({ id, title, icon, backgroundColor, details, children, onEdit, onDelete, fields, type }: WorkflowNodeProps) {
  return (
    <div className="relative rounded-lg size-full">
      <div className="absolute border border-neutral-200 border-solid inset-0 pointer-events-none rounded-lg shadow-[0px_4px_6px_-1px_rgba(0,0,0,0.1),0px_2px_4px_-2px_rgba(0,0,0,0.1)]" />
      <div className="flex flex-col items-end relative size-full">
        <div className="box-border content-stretch flex flex-col items-end justify-start p-[4px] relative size-full">
          <div className={cn("relative rounded-lg shrink-0 w-full", backgroundColor)}>
            <div className="absolute border border-neutral-200 border-solid inset-0 pointer-events-none rounded-lg shadow-[0px_4px_6px_0px_rgba(0,0,0,0.09)]" />
            <div className="flex flex-row items-center relative size-full">
              <div className="box-border content-stretch flex flex-row gap-1.5 items-center justify-start px-3 py-2.5 relative w-full">
                <div className="overflow-clip relative shrink-0 size-4">
                  {icon}
                </div>
                <div className="basis-0 font-medium grow leading-[0] min-h-px min-w-px not-italic relative shrink-0 text-[14px] text-left text-neutral-950">
                  <p className="block leading-[20px]">{title}</p>
                </div>
                {type === "trigger" && (
                  <Popover>
                    <PopoverTrigger asChild>
                      <button className="opacity-50 overflow-clip relative shrink-0 size-4 hover:opacity-75">
                        <MoreVertical className="size-4" />
                      </button>
                    </PopoverTrigger>
                    <PopoverContent className="w-48 p-1" align="end">
                      <div className="space-y-1">
                        <button 
                          onClick={() => onEdit?.({ id: id!, type: type!, title, fields })}
                          className="flex items-center gap-2 w-full px-2 py-1.5 text-sm rounded-sm hover:bg-gray-100"
                        >
                          <Edit className="size-4" />
                          Edit
                        </button>
                        <Separator />
                        <button 
                          onClick={() => onDelete?.(id!)}
                          className="flex items-center gap-2 w-full px-2 py-1.5 text-sm rounded-sm hover:bg-red-50 text-red-600"
                        >
                          <Trash2 className="size-4" />
                          Delete
                        </button>
                      </div>
                    </PopoverContent>
                  </Popover>
                )}
                {(type === "rule" || type === "action") && (
                  <Popover>
                    <PopoverTrigger asChild>
                      <button className="opacity-50 overflow-clip relative shrink-0 size-4 hover:opacity-75">
                        <MoreVertical className="size-4" />
                      </button>
                    </PopoverTrigger>
                    <PopoverContent className="w-48 p-1" align="end">
                      <div className="space-y-1">
                        <button 
                          onClick={() => onEdit?.({ id: id!, type: type!, title, fields })}
                          className="flex items-center gap-2 w-full px-2 py-1.5 text-sm rounded-sm hover:bg-gray-100"
                        >
                          <Edit className="size-4" />
                          Edit
                        </button>
                        <button 
                          onClick={() => {}}
                          className="flex items-center gap-2 w-full px-2 py-1.5 text-sm rounded-sm hover:bg-gray-100"
                        >
                          <Copy className="size-4" />
                          Duplicate
                        </button>
                        <Separator />
                        <button 
                          onClick={() => onDelete?.(id!)}
                          className="flex items-center gap-2 w-full px-2 py-1.5 text-sm rounded-sm hover:bg-red-50 text-red-600"
                        >
                          <Trash2 className="size-4" />
                          Delete
                        </button>
                      </div>
                    </PopoverContent>
                  </Popover>
                )}
                {type === "exit" && (
                  <button 
                    onClick={() => onDelete?.(id!)}
                    className="opacity-50 overflow-clip relative shrink-0 size-4 hover:opacity-75"
                  >
                    <MoreVertical className="size-4" />
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
      {details && details.length > 0 && (
        <div className="p-1">
          {details.map((detail, index) => (
            <div key={index} className="px-2 py-1.5 rounded-sm">
              <div className="text-sm text-neutral-950 leading-5">
                {detail.label && <span className="text-neutral-500">{detail.label} </span>}
                <span className="font-normal">{detail.value}</span>
              </div>
            </div>
          ))}
        </div>
      )}
      {children}
    </div>
  )
}

interface TriggerSectionProps {
  title: string
  isExpanded?: boolean
  onToggle?: () => void
  children: React.ReactNode
}

function TriggerSection({ title, isExpanded = true, onToggle, children }: TriggerSectionProps) {
  return (
    <div className="space-y-3">
      <button 
        onClick={onToggle}
        className="flex items-center justify-between w-full p-0 rounded-lg"
      >
        <div className="text-sm font-medium text-neutral-950 text-left">{title}</div>
        <ChevronDown className={cn("size-3 transition-transform", isExpanded && "rotate-180")} />
      </button>
      {isExpanded && (
        <div className="space-y-3">
          {children}
        </div>
      )}
    </div>
  )
}

export function WorkflowBuilder({ className }: { className?: string }) {
  const [searchTerm, setSearchTerm] = React.useState("")
  const [activeTab, setActiveTab] = React.useState<"triggers" | "rules" | "actions">("triggers")
  const [workflowNodes, setWorkflowNodes] = React.useState<Array<{ id: string; type: "trigger" | "rule" | "action"; title: string; icon: React.ReactNode; backgroundColor: string; details?: Array<{ label: string; value: string }>; fields?: any }>>([])
  const [isDragging, setIsDragging] = React.useState(false)
  const [draggedItem, setDraggedItem] = React.useState<any>(null)
  const [editingNode, setEditingNode] = React.useState<{ id: string; type: string; title: string; fields: any } | null>(null)
  const [isEditDialogOpen, setIsEditDialogOpen] = React.useState(false)
  const [zoomLevel, setZoomLevel] = React.useState(100)

  const triggerCategories = [
    {
      title: "User",
      triggers: [
        { 
          icon: <UserPlus className="size-4" />, 
          title: "User Registered",
          defaultFields: {
            userType: "Any User",
            registrationSource: "Website"
          }
        },
        { 
          icon: <UserMinus className="size-4" />, 
          title: "User Deleted",
          defaultFields: {
            deletionReason: "User Request",
            notifyAdmin: true
          }
        },
        { 
          icon: <UserCheck className="size-4" />, 
          title: "User Updated",
          defaultFields: {
            updatedField: "Profile Information",
            trackChanges: true
          }
        },
        { 
          icon: <UserCog className="size-4" />, 
          title: "User Role Changed",
          defaultFields: {
            previousRole: "Subscriber",
            newRole: "Administrator"
          }
        },
      ]
    },
    {
      title: "Subscriber",
      triggers: [
        { 
          icon: <Rss className="size-4" />, 
          title: "User Subscribed",
          defaultFields: {
            subscriptionList: "Main List",
            subscriptionSource: "Website Form"
          }
        },
        { 
          icon: <Ban className="size-4" />, 
          title: "User Unconfirmed",
          defaultFields: {
            confirmationTimeout: "24 hours",
            sendReminder: true
          }
        },
        { 
          icon: <UserX className="size-4" />, 
          title: "User Unsubscribed",
          defaultFields: {
            unsubscribeReason: "User Request",
            fromList: "All Lists"
          }
        },
      ]
    },
    {
      title: "Admin",
      triggers: [
        { 
          icon: <Mail className="size-4" />, 
          title: "Campaign sent",
          defaultFields: {
            campaignType: "Newsletter",
            recipientCount: "100+"
          }
        },
        { 
          icon: <MailX className="size-4" />, 
          title: "Campaign failed",
          defaultFields: {
            failureReason: "Server Error",
            retryAttempts: 3
          }
        },
      ]
    },
    {
      title: "Comment",
      triggers: [
        { 
          icon: <MessageSquare className="size-4" />, 
          title: "Comment Added",
          defaultFields: {
            postType: "Blog Post",
            moderationStatus: "Pending"
          }
        },
      ]
    },
    {
      title: "Order",
      triggers: [
        { 
          icon: <Package className="size-4" />, 
          title: "WooCommerce Order Completed",
          defaultFields: {
            orderValue: "$50+",
            paymentMethod: "Any"
          }
        },
        { 
          icon: <PackagePlus className="size-4" />, 
          title: "WooCommerce Order Created",
          defaultFields: {
            orderStatus: "Processing",
            productCategory: "Any"
          }
        },
        { 
          icon: <DollarSign className="size-4" />, 
          title: "WooCommerce Order Refunded",
          defaultFields: {
            refundAmount: "Full Amount",
            refundReason: "Customer Request"
          }
        },
      ]
    },
    {
      title: "Review",
      triggers: [
        { 
          icon: <Star className="size-4" />, 
          title: "New Product Review Posted",
          defaultFields: {
            minimumRating: "3 stars",
            productType: "Any Product"
          }
        },
      ]
    },
    {
      title: "Carts",
      triggers: [
        { 
          icon: <ShoppingCart className="size-4" />, 
          title: "Cart Abandoned",
          defaultFields: {
            abandonmentTime: "1 hour",
            cartValue: "$10+"
          }
        },
        { 
          icon: <ShoppingCart className="size-4" />, 
          title: "Cart Abandoned - Registered User Only",
          defaultFields: {
            abandonmentTime: "1 hour",
            cartValue: "$10+",
            userType: "Registered"
          }
        },
        { 
          icon: <ShoppingCart className="size-4" />, 
          title: "Cart Abandoned - Guests Only",
          defaultFields: {
            abandonmentTime: "1 hour",
            cartValue: "$10+",
            userType: "Guest"
          }
        },
      ]
    }
  ]

  const rulesCategories = [
    {
      title: "Subscriber",
      rules: [
        { 
          icon: <ListChecks className="size-4" />, 
          title: "Subscriber - List",
          defaultFields: {
            operator: "Matches",
            values: ["Main List", "Test List", "Some other list"]
          }
        },
        { 
          icon: <Filter className="size-4" />, 
          title: "Subscriber - Tag",
          defaultFields: {
            operator: "Matches any",
            values: ["VIP", "New Customer", "Premium"]
          }
        },
        { 
          icon: <Target className="size-4" />, 
          title: "Subscriber - Field",
          defaultFields: {
            operator: "Equals",
            values: ["Active Status", "Location", "Age"]
          }
        },
      ]
    },
    {
      title: "Time",
      rules: [
        { 
          icon: <Clock className="size-4" />, 
          title: "Time Delay",
          defaultFields: {
            operator: "Wait for",
            values: ["1 hour", "1 day", "1 week"]
          }
        },
        { 
          icon: <Clock className="size-4" />, 
          title: "Wait Until",
          defaultFields: {
            operator: "Wait until",
            values: ["9 AM", "12 PM", "6 PM"]
          }
        },
      ]
    },
    {
      title: "Conditions",
      rules: [
        { 
          icon: <Zap className="size-4" />, 
          title: "If/Else Condition",
          defaultFields: {
            operator: "If",
            values: ["True", "False"]
          }
        },
        { 
          icon: <Target className="size-4" />, 
          title: "Split Test",
          defaultFields: {
            operator: "Split",
            values: ["50%", "30%", "20%"]
          }
        },
      ]
    }
  ]

  const actionsCategories = [
    {
      title: "Email",
      actions: [
        { 
          icon: <MailCheck className="size-4" />, 
          title: "Send email",
          defaultFields: {
            subject: "Welcome to the fam",
            recipients: "80 Contacts selected",
            content: "Welcome to LuminaTech – we're truly excited to have you with us! 🎉\n\nI'm Amit Pathania, your dedicated Success Manager, and I'll be your main point of contact as you get started with us. Whether you're here to streamline your workflows, gain deeper insights from your data, or scale your team's productivity, we're committed to helping you achieve your goals."
          }
        },
        { 
          icon: <Mail className="size-4" />, 
          title: "Send broadcast",
          defaultFields: {
            subject: "Important Update",
            recipients: "All subscribers",
            content: "We have an important update to share with you..."
          }
        },
        { 
          icon: <MailX className="size-4" />, 
          title: "Stop email",
          defaultFields: {
            reason: "User unsubscribed",
            action: "Stop all emails"
          }
        },
      ]
    },
    {
      title: "SMS",
      actions: [
        { 
          icon: <Phone className="size-4" />, 
          title: "Send SMS",
          defaultFields: {
            message: "Hello! Important update for you.",
            recipients: "Phone contacts"
          }
        },
        { 
          icon: <MessageCircle className="size-4" />, 
          title: "Send WhatsApp",
          defaultFields: {
            message: "Hi there! Check out our latest update.",
            recipients: "WhatsApp contacts"
          }
        },
      ]
    },
    {
      title: "Subscriber",
      actions: [
        { 
          icon: <UserPlus className="size-4" />, 
          title: "Add to list",
          defaultFields: {
            list: "Main List",
            action: "Add subscriber"
          }
        },
        { 
          icon: <UserMinus className="size-4" />, 
          title: "Remove from list",
          defaultFields: {
            list: "Main List",
            action: "Remove subscriber"
          }
        },
        { 
          icon: <UserCog className="size-4" />, 
          title: "Update field",
          defaultFields: {
            field: "Status",
            value: "Active"
          }
        },
      ]
    }
  ]

  const handleZoomIn = () => {
    setZoomLevel(prev => Math.min(prev + 25, 200))
  }

  const handleZoomOut = () => {
    setZoomLevel(prev => Math.max(prev - 25, 50))
  }

  // Calculate actual height of an action node based on its content
  const calculateActionHeight = (action: any) => {
    let baseHeight = 54 // Base height for the action header (padding + icon + text)
    
    if (action.details && action.details.length > 0) {
      // Each detail line calculation:
      action.details.forEach((detail: any) => {
        // Short labels/values: ~28px, longer content might wrap: ~40-60px
        const isLongContent = detail.value && detail.value.length > 50
        if (isLongContent) {
          // Content might wrap - calculate based on estimated lines
          const estimatedLines = Math.ceil(detail.value.length / 50)
          baseHeight += Math.max(28, estimatedLines * 20 + 16) // 20px per line + padding
        } else {
          baseHeight += 28 // Standard single line height
        }
      })
      
      baseHeight += 8 // Additional padding for details container
    }
    
    // Add margins and rounded corners compensation
    baseHeight += 16 // Container padding
    
    return baseHeight
  }

  // Calculate cumulative positions for all actions
  const getActionPositions = () => {
    const actions = workflowNodes.filter(n => n.type === 'action')
    const rules = workflowNodes.filter(n => n.type === 'rule')
    
    let basePosition = 149 // After trigger with 35px connector (64px + 35px + 50px margin)
    if (rules.length === 1) {
      basePosition = 294 // After single rule (149px + 110px rule height + 35px connector)
    } else if (rules.length > 1) {
      basePosition = 364 // After multiple rules (184px + 110px rule height + 35px + 35px connectors)
    }
    
    const positions: { [key: string]: number } = {}
    let currentPosition = basePosition
    
    actions.forEach((action) => {
      positions[action.id] = currentPosition
      
      // Calculate height for current action and add spacing for next
      const actionHeight = calculateActionHeight(action)
      currentPosition += actionHeight + 32 // 32px gap between actions (reduced from 40px)
    })
    
    return positions
  }

  const handleDragStart = (e: React.DragEvent, item: any, type: "trigger" | "rule" | "action") => {
    setIsDragging(true)
    setDraggedItem({ ...item, type })
    e.dataTransfer.effectAllowed = 'copy'
  }

  const handleDragEnd = () => {
    setIsDragging(false)
    setDraggedItem(null)
  }

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault()
    e.dataTransfer.dropEffect = 'copy'
  }

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault()
    if (draggedItem) {
      handleAddItem(draggedItem, draggedItem.type)
    }
    setIsDragging(false)
    setDraggedItem(null)
  }

  const handleAddTrigger = (trigger: any) => {
    const newNode = {
      id: `node-${Date.now()}`,
      type: 'trigger' as const,
      title: trigger.title,
      icon: trigger.icon,
      backgroundColor: "bg-red-100",
      details: [], // Don't show details by default
      fields: trigger.defaultFields || {}
    }
    
    // Replace existing trigger if one exists, otherwise add new one
    const existingTriggerIndex = workflowNodes.findIndex(node => node.type === 'trigger')
    if (existingTriggerIndex !== -1) {
      const updatedNodes = [...workflowNodes]
      updatedNodes[existingTriggerIndex] = newNode
      setWorkflowNodes(updatedNodes)
    } else {
      setWorkflowNodes([...workflowNodes, newNode])
    }
  }

  const handleAddRule = (rule: any) => {
    const newNode = {
      id: `node-${Date.now()}`,
      type: 'rule' as const,
      title: rule.title,
      icon: rule.icon,
      backgroundColor: "bg-blue-100",
      details: rule.defaultFields ? [
        { label: "", value: rule.defaultFields.operator },
        { label: "", value: rule.defaultFields.values.join(", ") }
      ] : [],
      fields: rule.defaultFields || {}
    }
    setWorkflowNodes([...workflowNodes, newNode])
  }

  const handleAddAction = (action: any) => {
    const newNode = {
      id: `node-${Date.now()}`,
      type: 'action' as const,
      title: action.title,
      icon: action.icon,
      backgroundColor: "bg-green-100",
      details: action.defaultFields ? Object.entries(action.defaultFields).map(([key, value]) => ({
        label: key === 'subject' ? 'Subject:' : key === 'recipients' ? 'To:' : key === 'content' ? 'Content:' : `${key}:`,
        value: typeof value === 'string' ? value : String(value)
      })) : [],
      fields: action.defaultFields || {}
    }
    setWorkflowNodes([...workflowNodes, newNode])
  }

  const handleAddItem = (item: any, type: "trigger" | "rule" | "action") => {
    switch (type) {
      case 'trigger':
        handleAddTrigger(item)
        break
      case 'rule':
        handleAddRule(item)
        break
      case 'action':
        handleAddAction(item)
        break
    }
  }

  const handleDeleteNode = (nodeId: string) => {
    setWorkflowNodes(workflowNodes.filter(node => node.id !== nodeId))
  }

  const handleEditNode = (node: { id: string; type: string; title: string; fields: any }) => {
    setEditingNode(node)
    setIsEditDialogOpen(true)
  }

  const handleSaveEdit = (updatedFields: any) => {
    if (!editingNode) return

    setWorkflowNodes(prevNodes => 
      prevNodes.map(node => {
        if (node.id === editingNode.id) {
          let updatedDetails
          
          if (editingNode.type === 'rule') {
            updatedDetails = [
              { label: "", value: updatedFields.operator || editingNode.fields.operator },
              { label: "", value: (updatedFields.values || editingNode.fields.values).join(", ") }
            ]
          } else if (editingNode.type === 'trigger') {
            updatedDetails = Object.entries(updatedFields).map(([key, value]) => ({
              label: key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1') + ':',
              value: typeof value === 'string' ? value : String(value)
            }))
          } else {
            updatedDetails = Object.entries(updatedFields).map(([key, value]) => ({
              label: key === 'subject' ? 'Subject:' : key === 'recipients' ? 'To:' : key === 'content' ? 'Content:' : `${key}:`,
              value: typeof value === 'string' ? value : String(value)
            }))
          }

          return {
            ...node,
            fields: { ...node.fields, ...updatedFields },
            details: updatedDetails
          }
        }
        return node
      })
    )
    
    setIsEditDialogOpen(false)
    setEditingNode(null)
  }

  return (
    <div className={cn("bg-[#f6f5f8] min-h-screen", className)}>
      {/* Top Alert Bar */}
      <div className="bg-white relative">
        <div className="absolute border-b border-neutral-200 border-solid inset-x-0 bottom-0 pointer-events-none" />
        <div className="flex flex-row items-center relative size-full">
          <div className="box-border flex flex-row gap-3 items-center justify-start px-4 py-3 relative size-full">
            <div className="basis-0 box-border flex flex-row gap-3 grow items-start justify-start min-h-px min-w-px p-0 relative shrink-0">
              <div className="basis-0 box-border flex flex-col gap-1 grow items-start justify-center min-h-px min-w-px p-0 relative shrink-0">
                <div className="box-border flex flex-row gap-2.5 items-center justify-start p-0 relative shrink-0 w-full">
                  <div className="font-medium leading-[0] not-italic overflow-ellipsis overflow-hidden relative shrink-0 text-[14px] text-left text-neutral-950 text-nowrap">
                    <p className="text-overflow-inherit block leading-[20px] overflow-inherit whitespace-pre">
                      Alert Title
                    </p>
                  </div>
                  <div className="overflow-clip relative shrink-0 size-5">
                    <PenTool className="size-5" />
                  </div>
                </div>
              </div>
            </div>
            <div className="box-border flex flex-row gap-3 items-center justify-start p-0 relative shrink-0">
              <div className="box-border flex flex-row gap-2 h-9 items-center justify-center px-4 py-2 relative rounded-lg shrink-0">
                <div className="absolute border border-[#5e19cf] border-solid inset-0 pointer-events-none rounded-lg shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)]" />
                <div className="flex flex-col font-medium justify-center leading-[0] not-italic relative shrink-0 text-[#5e19cf] text-[14px] text-left text-nowrap">
                  <p className="block leading-[20px] whitespace-pre">
                    Save and Exit
                  </p>
                </div>
              </div>
              <div className="bg-[#5e19cf] box-border flex flex-row gap-2 h-9 items-center justify-center px-4 py-2 relative rounded-lg shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] shrink-0">
                <div className="flex flex-col font-medium justify-center leading-[0] not-italic relative shrink-0 text-[14px] text-left text-neutral-50 text-nowrap">
                  <p className="block leading-[20px] whitespace-pre">
                    Activate Workflow
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="flex gap-0">
        {/* Main Canvas Area */}
        <div className="flex-1 py-6 px-6 relative overflow-hidden">
          {/* Zoom Controls */}
          <div className="absolute top-6 right-6 z-10">
            <div className="bg-white relative rounded-lg">
              <div className="absolute border border-slate-200 border-solid inset-0 pointer-events-none rounded-lg shadow-[0px_4px_6px_0px_rgba(0,0,0,0.09)]" />
              <div className="flex flex-row items-center justify-center relative w-full">
                <div className="box-border content-stretch flex flex-row gap-3 items-center justify-center p-[8px] relative w-full">
                  <div className="box-border content-stretch flex flex-row gap-2 items-center justify-start p-0 relative shrink-0">
                    <button
                      onClick={handleZoomOut}
                      className="relative shrink-0 size-4 hover:opacity-75 transition-opacity"
                      disabled={zoomLevel <= 50}
                    >
                      <svg className="size-4" viewBox="0 0 16 16" fill="none">
                        <path d="M4 8h8" stroke="#71717A" strokeWidth="1.5" strokeLinecap="round"/>
                      </svg>
                    </button>
                    <div className="font-['Inter:Medium',_sans-serif] font-medium leading-[0] not-italic relative shrink-0 text-[9.95699px] text-left text-nowrap text-zinc-500">
                      <p className="block leading-[17.0691px] whitespace-pre">{zoomLevel}%</p>
                    </div>
                    <button
                      onClick={handleZoomIn}
                      className="relative shrink-0 size-4 hover:opacity-75 transition-opacity"
                      disabled={zoomLevel >= 200}
                    >
                      <svg className="size-4" viewBox="0 0 16 16" fill="none">
                        <path d="M8 4v8M4 8h8" stroke="#71717A" strokeWidth="1.5" strokeLinecap="round"/>
                      </svg>
                    </button>
                  </div>
                  <button className="relative shrink-0 size-4 hover:opacity-75 transition-opacity">
                    <svg className="size-4" viewBox="0 0 16 16" fill="none">
                      <path d="M2 4h3M2 8h3M2 12h3M8 4h3M8 8h3M8 12h3" stroke="#71717A" strokeWidth="1.5" strokeLinecap="round"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          {/* Scrollable Canvas Container */}
          <div className="h-full overflow-auto">
            <div 
              className={cn(
                "relative min-h-[800px] rounded-lg border-2 border-dashed transition-colors",
                isDragging ? "border-purple-300 bg-purple-50/50" : "border-transparent"
              )}
              style={{
                transform: `scale(${zoomLevel / 100})`,
                transformOrigin: 'top left',
                width: `${10000 / zoomLevel}%`,
                minHeight: `${80000 / zoomLevel}px`
              }}
              onDragOver={handleDragOver}
              onDrop={handleDrop}
            >
            {/* Empty State */}
            {workflowNodes.length === 0 && (
              <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
                <div className="text-lg font-medium text-neutral-600 mb-2">
                  Start building your workflow
                </div>
                <div className="text-sm text-neutral-500 max-w-md">
                  Add triggers, rules, and actions from the sidebar to create your automation workflow. 
                  You can drag and drop items or click to add them.
                </div>
              </div>
            )}

            {/* Workflow Connectors */}
            {workflowNodes.length > 0 && (() => {
              const rules = workflowNodes.filter(n => n.type === 'rule')
              const actions = workflowNodes.filter(n => n.type === 'action')
              const hasRules = rules.length > 0
              const hasActions = actions.length > 0
              
              return (
                <>
                  {/* Connector from trigger to rules/actions */}
                  {(hasRules || hasActions) && (
                    <>
                      {hasRules && rules.length === 1 ? (
                        // Single rule - direct connection to center
                        <div className="absolute top-[114px] left-1/2 transform -translate-x-1/2">
                          <div className="w-0.5 h-[35px] border-l-2 border-dashed border-neutral-300 relative">
                            <div className="absolute -bottom-0.5 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-[4px] border-r-[4px] border-t-[6px] border-l-transparent border-r-transparent border-t-neutral-300"></div>
                          </div>
                        </div>
                      ) : hasRules && rules.length > 1 ? (
                        // Multiple rules - split connections dynamically
                        <>
                          {/* Main vertical line from trigger */}
                          <div className="absolute top-[114px] left-1/2 transform -translate-x-1/2">
                            <div className="w-0.5 h-[35px] border-l-2 border-dashed border-neutral-300"></div>
                          </div>
                          {/* Horizontal split line - spans across all rule positions */}
                          <div 
                            className="absolute top-[149px] h-0.5 border-t-2 border-dashed border-neutral-300"
                            style={{ 
                              left: `${100 / (rules.length + 1)}%`,
                              width: `${100 - (200 / (rules.length + 1))}%`
                            }}
                          ></div>
                          {/* Branches to each rule */}
                          {rules.map((_, index) => {
                            const totalRules = rules.length
                            const spacing = 100 / (totalRules + 1)
                            const leftPercentage = spacing * (index + 1)
                            
                            return (
                              <div 
                                key={`connector-${index}`}
                                className="absolute top-[149px] transform -translate-x-1/2"
                                style={{ left: `${leftPercentage}%` }}
                              >
                                <div className="w-0.5 h-[35px] border-l-2 border-dashed border-neutral-300 relative">
                                  <div className="absolute -bottom-0.5 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-[4px] border-r-[4px] border-t-[6px] border-l-transparent border-r-transparent border-t-neutral-300"></div>
                                </div>
                              </div>
                            )
                          })}
                        </>
                      ) : hasActions && !hasRules ? (
                        // Direct to actions (no rules)
                        <div className="absolute top-[114px] left-1/2 transform -translate-x-1/2">
                          <div className="w-0.5 h-[35px] border-l-2 border-dashed border-neutral-300 relative">
                            <div className="absolute -bottom-0.5 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-[4px] border-r-[4px] border-t-[6px] border-l-transparent border-r-transparent border-t-neutral-300"></div>
                          </div>
                        </div>
                      ) : null}
                    </>
                  )}
                  
                  {/* Connector from rules to actions */}
                  {hasRules && hasActions && (
                    <>
                      {rules.length === 1 ? (
                        // Single rule to actions
                        <div className="absolute top-[259px] left-1/2 transform -translate-x-1/2">
                          <div className="w-0.5 h-[35px] border-l-2 border-dashed border-neutral-300 relative">
                            <div className="absolute -bottom-0.5 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-[4px] border-r-[4px] border-t-[6px] border-l-transparent border-r-transparent border-t-neutral-300"></div>
                          </div>
                        </div>
                      ) : (
                        // Multiple rules to actions - merge connections dynamically
                        <>
                          {/* Branches from each rule */}
                          {rules.map((_, index) => {
                            const totalRules = rules.length
                            const spacing = 100 / (totalRules + 1)
                            const leftPercentage = spacing * (index + 1)
                            
                            return (
                              <div 
                                key={`rule-to-action-${index}`}
                                className="absolute top-[294px] transform -translate-x-1/2"
                                style={{ left: `${leftPercentage}%` }}
                              >
                                <div className="w-0.5 h-[35px] border-l-2 border-dashed border-neutral-300"></div>
                              </div>
                            )
                          })}
                          {/* Horizontal merge line - spans across all rule positions */}
                          <div 
                            className="absolute top-[329px] h-0.5 border-t-2 border-dashed border-neutral-300"
                            style={{ 
                              left: `${100 / (rules.length + 1)}%`,
                              width: `${100 - (200 / (rules.length + 1))}%`
                            }}
                          ></div>
                          {/* Final connector to actions */}
                          <div className="absolute top-[329px] left-1/2 transform -translate-x-1/2">
                            <div className="w-0.5 h-[35px] border-l-2 border-dashed border-neutral-300 relative">
                              <div className="absolute -bottom-0.5 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-[4px] border-r-[4px] border-t-[6px] border-l-transparent border-r-transparent border-t-neutral-300"></div>
                            </div>
                          </div>
                        </>
                      )}
                    </>
                  )}
                  
                  {/* Dynamic connectors between actions */}
                  {actions.map((action, index) => {
                    if (index < actions.length - 1) {
                      // Calculate dynamic connector position based on actual action heights
                      const actionPositions = getActionPositions()
                      const currentActionPosition = actionPositions[action.id]
                      const currentActionHeight = calculateActionHeight(action)
                      const connectorPosition = currentActionPosition + currentActionHeight + 8 // +8px gap from bottom of action
                      
                      return (
                        <div 
                          key={`connector-${index}`}
                          className="absolute left-1/2 transform -translate-x-1/2"
                          style={{ top: `${connectorPosition}px` }}
                        >
                          <div className="w-0.5 h-[35px] border-l-2 border-dashed border-neutral-300 relative">
                            <div className="absolute -bottom-0.5 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-[4px] border-r-[4px] border-t-[6px] border-l-transparent border-r-transparent border-t-neutral-300"></div>
                          </div>
                        </div>
                      )
                    }
                    return null
                  })}
                  
                  {/* Connector to exit */}
                  {(() => {
                    let connectorPosition = 114 // Default after trigger (adjusted to match trigger bottom)
                    
                    if (hasActions) {
                      // Connect from last action to exit using dynamic positioning
                      const actionPositions = getActionPositions()
                      const lastAction = actions[actions.length - 1]
                      const lastActionPosition = actionPositions[lastAction.id]
                      const lastActionHeight = calculateActionHeight(lastAction)
                      connectorPosition = lastActionPosition + lastActionHeight + 8 // +8px gap from bottom of last action
                    } else if (hasRules) {
                      // Connect from rules to exit (no actions)
                      if (rules.length === 1) {
                        connectorPosition = 259
                      } else {
                        // Multiple rules - merge to center then to exit
                        return (
                          <>
                            {/* Branches from each rule */}
                            {rules.map((_, index) => {
                              const totalRules = rules.length
                              const spacing = 100 / (totalRules + 1)
                              const leftPercentage = spacing * (index + 1)
                              
                              return (
                                <div 
                                  key={`rule-to-exit-${index}`}
                                  className="absolute top-[294px] transform -translate-x-1/2"
                                  style={{ left: `${leftPercentage}%` }}
                                >
                                  <div className="w-0.5 h-[35px] border-l-2 border-dashed border-neutral-300"></div>
                                </div>
                              )
                            })}
                            {/* Horizontal merge line */}
                            <div 
                              className="absolute top-[329px] h-0.5 border-t-2 border-dashed border-neutral-300"
                              style={{ 
                                left: `${100 / (rules.length + 1)}%`,
                                width: `${100 - (200 / (rules.length + 1))}%`
                              }}
                            ></div>
                            {/* Final connector to exit */}
                            <div className="absolute top-[329px] left-1/2 transform -translate-x-1/2">
                              <div className="w-0.5 h-[35px] border-l-2 border-dashed border-neutral-300 relative">
                                <div className="absolute -bottom-0.5 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-[4px] border-r-[4px] border-t-[6px] border-l-transparent border-r-transparent border-t-neutral-300"></div>
                              </div>
                            </div>
                          </>
                        )
                      }
                    }
                    
                    return (
                      <div className="absolute left-1/2 transform -translate-x-1/2" style={{ top: `${connectorPosition}px` }}>
                        <div className="w-0.5 h-[35px] border-l-2 border-dashed border-neutral-300 relative">
                          <div className="absolute -bottom-0.5 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-[4px] border-r-[4px] border-t-[6px] border-l-transparent border-r-transparent border-t-neutral-300"></div>
                        </div>
                      </div>
                    )
                  })()}
                </>
              )
            })()}

            {/* Dynamic Workflow Nodes */}
            {workflowNodes.map((node) => {
              // Calculate position based on node type and workflow structure
              let position = "top-16 left-1/2 transform -translate-x-1/2"
              
              // Multiple nodes - arrange by type
              const rules = workflowNodes.filter(n => n.type === 'rule')
              
              const ruleIndex = rules.findIndex(n => n.id === node.id)
              
              if (node.type === 'trigger') {
                position = "top-16 left-1/2 transform -translate-x-1/2"
              } else if (node.type === 'rule') {
                if (rules.length === 1) {
                  position = "top-[149px] left-1/2 transform -translate-x-1/2"
                } else {
                  // Multiple rules - distribute evenly across available space
                  // Calculate percentage position based on rule index and total count
                  const totalRules = rules.length
                  const spacing = 100 / (totalRules + 1) // Add 1 to create equal spacing on both sides
                  const leftPercentage = spacing * (ruleIndex + 1) // +1 because index starts at 0
                  position = `top-[184px] transform -translate-x-1/2`
                  
                  return (
                    <div key={node.id} className={`absolute ${position}`} style={{ left: `${leftPercentage}%` }}>
                      <div className="w-[284px]">
                        <WorkflowNode
                          id={node.id}
                          title={node.title}
                          icon={node.icon}
                          backgroundColor={node.backgroundColor}
                          details={node.details}
                          onEdit={handleEditNode}
                          onDelete={handleDeleteNode}
                          fields={node.fields}
                          type={node.type}
                        />
                      </div>
                    </div>
                  )
                }
              } else if (node.type === 'action') {
                // Calculate dynamic position for actions using actual heights
                const actionPositions = getActionPositions()
                const actionPosition = actionPositions[node.id]
                position = `left-1/2 transform -translate-x-1/2`
                
                return (
                  <div key={node.id} className={`absolute ${position}`} style={{ top: `${actionPosition}px` }}>
                    <div className="w-[284px]">
                      <WorkflowNode
                        id={node.id}
                        title={node.title}
                        icon={node.icon}
                        backgroundColor={node.backgroundColor}
                        details={node.details}
                        onEdit={handleEditNode}
                        onDelete={handleDeleteNode}
                        fields={node.fields}
                        type={node.type}
                      />
                    </div>
                  </div>
                )
              }
              
              return (
                <div key={node.id} className={`absolute ${position}`}>
                  <div className="w-[284px]">
                    <WorkflowNode
                      id={node.id}
                      title={node.title}
                      icon={node.icon}
                      backgroundColor={node.backgroundColor}
                      details={node.details}
                      onEdit={handleEditNode}
                      onDelete={handleDeleteNode}
                      fields={node.fields}
                      type={node.type}
                    />
                  </div>
                </div>
              )
            })}

            {/* Exit Node - only show when there are workflow nodes */}
            {workflowNodes.length > 0 && (() => {
              const rules = workflowNodes.filter(n => n.type === 'rule')
              const actions = workflowNodes.filter(n => n.type === 'action')
              
              // Calculate position based on the last node in the workflow using dynamic heights
              let exitPosition = 200 // Minimum position to ensure it's always below trigger
              
              if (actions.length > 0) {
                // If there are actions, place exit after the last action using dynamic positioning
                const actionPositions = getActionPositions()
                const lastAction = actions[actions.length - 1]
                const lastActionPosition = actionPositions[lastAction.id]
                const lastActionHeight = calculateActionHeight(lastAction)
                exitPosition = lastActionPosition + lastActionHeight + 67 // +32px gap + 35px connector
              } else if (rules.length === 1) {
                // Single rule but no actions
                exitPosition = 294 // After single rule with 35px connector (149px + 110px rule height + 35px)
              } else if (rules.length > 1) {
                // Multiple rules but no actions
                exitPosition = 364 // After multiple rules with merge and 35px connector (184px + 110px + 35px + 35px)
              }
              
              return (
                <div className="absolute left-1/2 transform -translate-x-1/2" style={{ top: `${exitPosition}px` }}>
                  <div className="w-[108px]">
                    <div className="relative rounded-lg size-full">
                      <div className="absolute border border-neutral-200 border-solid inset-0 pointer-events-none rounded-lg shadow-[0px_4px_6px_-1px_rgba(0,0,0,0.1),0px_2px_4px_-2px_rgba(0,0,0,0.1)]" />
                      <div className="flex flex-col items-end relative size-full">
                        <div className="box-border content-stretch flex flex-col items-end justify-start p-[4px] relative size-full">
                          <div className="bg-gray-200 relative rounded-lg shrink-0 w-full">
                            <div className="absolute border border-neutral-200 border-solid inset-0 pointer-events-none rounded-lg shadow-[0px_4px_6px_0px_rgba(0,0,0,0.09)]" />
                            <div className="flex flex-row items-center relative size-full">
                              <div className="box-border content-stretch flex flex-row gap-1.5 items-center justify-start px-3 py-2.5 relative w-full">
                                <div className="overflow-clip relative shrink-0 size-4">
                                  <LogOut className="size-4" />
                                </div>
                                <div className="basis-0 font-medium grow leading-[0] min-h-px min-w-px not-italic relative shrink-0 text-[14px] text-left text-neutral-950">
                                  <p className="block leading-[20px]">Exit</p>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              )
            })()}

            {/* Progress Indicator - only show when there are workflow nodes */}
            {workflowNodes.length > 0 && (
              <div className="fixed bottom-8 right-8 z-10">
                <Card className="bg-white border border-slate-200 shadow-sm">
                  <CardContent className="flex items-center gap-2 p-2">
                    <div className="size-4 bg-green-500 rounded-sm" />
                    <span className="text-xs font-medium text-zinc-500">{zoomLevel}%</span>
                    <div className="size-4 bg-blue-500 rounded-sm" />
                    <div className="size-4 bg-gray-300 rounded-sm" />
                  </CardContent>
                </Card>
              </div>
            )}
            </div>
          </div>
        </div>

        {/* Right Sidebar - Triggers Panel */}
        <Card className="w-[268px] bg-white border-l border-slate-200">
          <CardContent className="p-6 space-y-6">
            {/* Search */}
            <div className="space-y-4">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 size-4 text-neutral-500" />
                <Input
                  placeholder="Search by step name"
                  value={searchTerm}
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearchTerm(e.target.value)}
                  className="pl-10"
                />
              </div>

              {/* Tabs */}
              <div className="w-full">
                <div className="flex border-b border-neutral-200">
                  <button 
                    onClick={() => setActiveTab("triggers")}
                    className={cn(
                      "flex-1 px-2.5 py-2 text-sm font-normal focus:outline-none",
                      activeTab === "triggers" 
                        ? "text-[#5e19cf] border-b-2 border-[#5e19cf]" 
                        : "text-neutral-500 hover:text-neutral-700"
                    )}
                  >
                    Triggers
                  </button>
                  <button 
                    onClick={() => setActiveTab("rules")}
                    className={cn(
                      "flex-1 px-2.5 py-2 text-sm font-normal focus:outline-none",
                      activeTab === "rules" 
                        ? "text-[#5e19cf] border-b-2 border-[#5e19cf]" 
                        : "text-neutral-500 hover:text-neutral-700"
                    )}
                  >
                    Rules
                  </button>
                  <button 
                    onClick={() => setActiveTab("actions")}
                    className={cn(
                      "flex-1 px-2.5 py-2 text-sm font-normal focus:outline-none",
                      activeTab === "actions" 
                        ? "text-[#5e19cf] border-b-2 border-[#5e19cf]" 
                        : "text-neutral-500 hover:text-neutral-700"
                    )}
                  >
                    Action
                  </button>
                </div>

                <div className="mt-2">
                  {activeTab === "triggers" && (
                    <>
                      <div className="text-sm font-medium text-neutral-500 leading-5 mb-4">
                        Workflow require a trigger to start. Choose a trigger below, and drag it to the canvas.
                      </div>

                      <div className="space-y-6">
                        {triggerCategories.map((category) => (
                          <TriggerSection key={category.title} title={category.title}>
                            <div className="space-y-3">
                              {category.triggers.map((trigger, index) => (
                                <div 
                                  key={index} 
                                  onClick={() => handleAddTrigger(trigger)} 
                                  className="cursor-pointer"
                                  draggable
                                  onDragStart={(e) => handleDragStart(e, trigger, "trigger")}
                                  onDragEnd={handleDragEnd}
                                >
                                  <TriggerItem
                                    icon={trigger.icon}
                                    title={trigger.title}
                                    category={category.title}
                                  />
                                </div>
                              ))}
                            </div>
                          </TriggerSection>
                        ))}
                      </div>
                    </>
                  )}

                  {activeTab === "rules" && (
                    <>
                      <div className="text-sm font-medium text-neutral-500 leading-5 mb-4">
                        Add rules to control when your workflow runs. Rules are optional but help target the right audience.
                      </div>

                      <div className="space-y-6">
                        {rulesCategories.map((category) => (
                          <TriggerSection key={category.title} title={category.title}>
                            <div className="space-y-3">
                              {category.rules.map((rule, index) => (
                                <div 
                                  key={index} 
                                  onClick={() => handleAddRule(rule)} 
                                  className="cursor-pointer"
                                  draggable
                                  onDragStart={(e) => handleDragStart(e, rule, "rule")}
                                  onDragEnd={handleDragEnd}
                                >
                                  <TriggerItem
                                    icon={rule.icon}
                                    title={rule.title}
                                    category={category.title}
                                  />
                                </div>
                              ))}
                            </div>
                          </TriggerSection>
                        ))}
                      </div>
                    </>
                  )}

                  {activeTab === "actions" && (
                    <>
                      <div className="text-sm font-medium text-neutral-500 leading-5 mb-4">
                        Add actions to define what happens in your workflow. Actions are the actual steps performed.
                      </div>

                      <div className="space-y-6">
                        {actionsCategories.map((category) => (
                          <TriggerSection key={category.title} title={category.title}>
                            <div className="space-y-3">
                              {category.actions.map((action, index) => (
                                <div 
                                  key={index} 
                                  onClick={() => handleAddAction(action)} 
                                  className="cursor-pointer"
                                  draggable
                                  onDragStart={(e) => handleDragStart(e, action, "action")}
                                  onDragEnd={handleDragEnd}
                                >
                                  <TriggerItem
                                    icon={action.icon}
                                    title={action.title}
                                    category={category.title}
                                  />
                                </div>
                              ))}
                            </div>
                          </TriggerSection>
                        ))}
                      </div>
                    </>
                  )}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Right Sidebar - Editor/Settings */}
        <Card className="w-[86px] bg-white border-l border-slate-200 shadow-sm">
          <CardContent className="p-3 space-y-6 relative">
            <div className="flex flex-col items-center gap-1 py-2">
              <div className="relative size-7 rounded-lg flex items-center justify-center border border-neutral-400 opacity-50">
                <PenTool className="size-4" />
              </div>
              <div className="text-xs text-zinc-800 text-center">Editor</div>
            </div>
            
            <div className="flex flex-col items-center gap-1 py-2">
              <div className="size-7 rounded-lg flex items-center justify-center">
                <Settings className="size-4" />
              </div>
              <div className="text-xs text-zinc-800 text-center">Settings</div>
            </div>

            {/* Active indicator for Editor */}
            <div className="absolute left-0 top-3 w-1 h-15 bg-slate-900 rounded-r-full" />
          </CardContent>
        </Card>
      </div>

      {/* Edit Dialog */}
      <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
        <DialogContent className="sm:max-w-[600px]">
          <DialogHeader>
            <DialogTitle>Edit {editingNode?.type === 'trigger' ? 'Trigger' : editingNode?.type === 'rule' ? 'Rule' : 'Action'}</DialogTitle>
            <DialogDescription>
              Configure the settings for "{editingNode?.title}".
            </DialogDescription>
          </DialogHeader>
          
          {editingNode && (
            <EditNodeForm 
              node={editingNode} 
              onSave={handleSaveEdit}
              onCancel={() => setIsEditDialogOpen(false)}
            />
          )}
        </DialogContent>
      </Dialog>
    </div>
  )
}

// Edit Node Form Component
interface EditNodeFormProps {
  node: { id: string; type: string; title: string; fields: any }
  onSave: (fields: any) => void
  onCancel: () => void
}

function EditNodeForm({ node, onSave, onCancel }: EditNodeFormProps) {
  const [formFields, setFormFields] = React.useState<Record<string, any>>(node.fields || {})

  const handleSave = () => {
    onSave(formFields)
  }

  const updateField = (key: string, value: any) => {
    setFormFields((prev: Record<string, any>) => ({ ...prev, [key]: value }))
  }

  if (node.type === 'trigger') {
    return (
      <div className="space-y-4">
        {/* User Role Changed trigger fields */}
        {formFields.previousRole !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="previousRole">Previous Role</Label>
            <Select value={formFields.previousRole || ''} onValueChange={(value: string) => updateField('previousRole', value)}>
              <SelectTrigger>
                <SelectValue placeholder="Select previous role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Subscriber">Subscriber</SelectItem>
                <SelectItem value="Contributor">Contributor</SelectItem>
                <SelectItem value="Author">Author</SelectItem>
                <SelectItem value="Editor">Editor</SelectItem>
                <SelectItem value="Administrator">Administrator</SelectItem>
              </SelectContent>
            </Select>
          </div>
        )}

        {formFields.newRole !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="newRole">New Role</Label>
            <Select value={formFields.newRole || ''} onValueChange={(value: string) => updateField('newRole', value)}>
              <SelectTrigger>
                <SelectValue placeholder="Select new role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Subscriber">Subscriber</SelectItem>
                <SelectItem value="Contributor">Contributor</SelectItem>
                <SelectItem value="Author">Author</SelectItem>
                <SelectItem value="Editor">Editor</SelectItem>
                <SelectItem value="Administrator">Administrator</SelectItem>
              </SelectContent>
            </Select>
          </div>
        )}

        {/* User Registration trigger fields */}
        {formFields.userType !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="userType">User Type</Label>
            <Select value={formFields.userType || ''} onValueChange={(value: string) => updateField('userType', value)}>
              <SelectTrigger>
                <SelectValue placeholder="Select user type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Any User">Any User</SelectItem>
                <SelectItem value="New User">New User</SelectItem>
                <SelectItem value="Returning User">Returning User</SelectItem>
              </SelectContent>
            </Select>
          </div>
        )}

        {formFields.registrationSource !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="registrationSource">Registration Source</Label>
            <Select value={formFields.registrationSource || ''} onValueChange={(value: string) => updateField('registrationSource', value)}>
              <SelectTrigger>
                <SelectValue placeholder="Select registration source" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Website">Website</SelectItem>
                <SelectItem value="Admin Created">Admin Created</SelectItem>
                <SelectItem value="Import">Import</SelectItem>
                <SelectItem value="API">API</SelectItem>
              </SelectContent>
            </Select>
          </div>
        )}

        {/* Subscription trigger fields */}
        {formFields.subscriptionList !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="subscriptionList">Subscription List</Label>
            <Select value={formFields.subscriptionList || ''} onValueChange={(value: string) => updateField('subscriptionList', value)}>
              <SelectTrigger>
                <SelectValue placeholder="Select list" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Main List">Main List</SelectItem>
                <SelectItem value="Newsletter">Newsletter</SelectItem>
                <SelectItem value="VIP List">VIP List</SelectItem>
                <SelectItem value="Test List">Test List</SelectItem>
              </SelectContent>
            </Select>
          </div>
        )}

        {formFields.subscriptionSource !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="subscriptionSource">Subscription Source</Label>
            <Input
              id="subscriptionSource"
              value={formFields.subscriptionSource || ''}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateField('subscriptionSource', e.target.value)}
              placeholder="e.g., Website Form, Pop-up, Import"
            />
          </div>
        )}

        {/* Campaign trigger fields */}
        {formFields.campaignType !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="campaignType">Campaign Type</Label>
            <Select value={formFields.campaignType || ''} onValueChange={(value: string) => updateField('campaignType', value)}>
              <SelectTrigger>
                <SelectValue placeholder="Select campaign type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Newsletter">Newsletter</SelectItem>
                <SelectItem value="Promotional">Promotional</SelectItem>
                <SelectItem value="Transactional">Transactional</SelectItem>
                <SelectItem value="Welcome Series">Welcome Series</SelectItem>
              </SelectContent>
            </Select>
          </div>
        )}

        {formFields.recipientCount !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="recipientCount">Recipient Count</Label>
            <Input
              id="recipientCount"
              value={formFields.recipientCount || ''}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateField('recipientCount', e.target.value)}
              placeholder="e.g., 100+, 50-100, <50"
            />
          </div>
        )}

        {/* Order trigger fields */}
        {formFields.orderValue !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="orderValue">Order Value</Label>
            <Input
              id="orderValue"
              value={formFields.orderValue || ''}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateField('orderValue', e.target.value)}
              placeholder="e.g., $50+, $100-200"
            />
          </div>
        )}

        {formFields.paymentMethod !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="paymentMethod">Payment Method</Label>
            <Select value={formFields.paymentMethod || ''} onValueChange={(value: string) => updateField('paymentMethod', value)}>
              <SelectTrigger>
                <SelectValue placeholder="Select payment method" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Any">Any</SelectItem>
                <SelectItem value="Credit Card">Credit Card</SelectItem>
                <SelectItem value="PayPal">PayPal</SelectItem>
                <SelectItem value="Bank Transfer">Bank Transfer</SelectItem>
                <SelectItem value="Cash on Delivery">Cash on Delivery</SelectItem>
              </SelectContent>
            </Select>
          </div>
        )}

        {/* Cart abandonment trigger fields */}
        {formFields.abandonmentTime !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="abandonmentTime">Abandonment Time</Label>
            <Select value={formFields.abandonmentTime || ''} onValueChange={(value: string) => updateField('abandonmentTime', value)}>
              <SelectTrigger>
                <SelectValue placeholder="Select time" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="15 minutes">15 minutes</SelectItem>
                <SelectItem value="30 minutes">30 minutes</SelectItem>
                <SelectItem value="1 hour">1 hour</SelectItem>
                <SelectItem value="2 hours">2 hours</SelectItem>
                <SelectItem value="24 hours">24 hours</SelectItem>
              </SelectContent>
            </Select>
          </div>
        )}

        {formFields.cartValue !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="cartValue">Cart Value</Label>
            <Input
              id="cartValue"
              value={formFields.cartValue || ''}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateField('cartValue', e.target.value)}
              placeholder="e.g., $10+, $50-100"
            />
          </div>
        )}

        {/* Other common fields */}
        {formFields.deletionReason !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="deletionReason">Deletion Reason</Label>
            <Input
              id="deletionReason"
              value={formFields.deletionReason || ''}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateField('deletionReason', e.target.value)}
              placeholder="Reason for user deletion"
            />
          </div>
        )}

        {formFields.updatedField !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="updatedField">Updated Field</Label>
            <Input
              id="updatedField"
              value={formFields.updatedField || ''}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateField('updatedField', e.target.value)}
              placeholder="Which field was updated"
            />
          </div>
        )}

        {formFields.minimumRating !== undefined && (
          <div className="space-y-2">
            <Label htmlFor="minimumRating">Minimum Rating</Label>
            <Select value={formFields.minimumRating || ''} onValueChange={(value: string) => updateField('minimumRating', value)}>
              <SelectTrigger>
                <SelectValue placeholder="Select minimum rating" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="1 star">1 star</SelectItem>
                <SelectItem value="2 stars">2 stars</SelectItem>
                <SelectItem value="3 stars">3 stars</SelectItem>
                <SelectItem value="4 stars">4 stars</SelectItem>
                <SelectItem value="5 stars">5 stars</SelectItem>
              </SelectContent>
            </Select>
          </div>
        )}

        <DialogFooter>
          <Button variant="outline" onClick={onCancel}>
            Cancel
          </Button>
          <Button onClick={handleSave}>
            Save Changes
          </Button>
        </DialogFooter>
      </div>
    )
  }

  if (node.type === 'rule') {
    return (
      <div className="space-y-4">
        <div className="space-y-2">
          <Label htmlFor="operator">Rule Operator</Label>
          <Select value={formFields.operator || ''} onValueChange={(value: string) => updateField('operator', value)}>
            <SelectTrigger>
              <SelectValue placeholder="Select operator" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="Matches">Matches</SelectItem>
              <SelectItem value="Matches any">Matches any</SelectItem>
              <SelectItem value="Equals">Equals</SelectItem>
              <SelectItem value="Wait for">Wait for</SelectItem>
              <SelectItem value="Wait until">Wait until</SelectItem>
              <SelectItem value="If">If</SelectItem>
              <SelectItem value="Split">Split</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-2">
          <Label htmlFor="values">Values</Label>
          <Textarea
            id="values"
            value={(formFields.values || []).join(', ')}
            onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => updateField('values', e.target.value.split(', ').filter((v: string) => v.trim()))}
            placeholder="Enter values separated by commas"
            rows={3}
          />
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={onCancel}>
            Cancel
          </Button>
          <Button onClick={handleSave}>
            Save Changes
          </Button>
        </DialogFooter>
      </div>
    )
  }

  // Action form
  return (
    <div className="space-y-4">
      {formFields.subject !== undefined && (
        <div className="space-y-2">
          <Label htmlFor="subject">Subject</Label>
          <Input
            id="subject"
            value={formFields.subject || ''}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateField('subject', e.target.value)}
            placeholder="Email subject"
          />
        </div>
      )}

      {formFields.recipients !== undefined && (
        <div className="space-y-2">
          <Label htmlFor="recipients">Recipients</Label>
          <Input
            id="recipients"
            value={formFields.recipients || ''}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateField('recipients', e.target.value)}
            placeholder="Recipients"
          />
        </div>
      )}

      {formFields.content !== undefined && (
        <div className="space-y-2">
          <Label htmlFor="content">Content</Label>
          <Textarea
            id="content"
            value={formFields.content || ''}
            onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => updateField('content', e.target.value)}
            placeholder="Email content"
            rows={6}
          />
        </div>
      )}

      {formFields.message !== undefined && (
        <div className="space-y-2">
          <Label htmlFor="message">Message</Label>
          <Textarea
            id="message"
            value={formFields.message || ''}
            onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => updateField('message', e.target.value)}
            placeholder="Message content"
            rows={4}
          />
        </div>
      )}

      {formFields.list !== undefined && (
        <div className="space-y-2">
          <Label htmlFor="list">List</Label>
          <Select value={formFields.list || ''} onValueChange={(value: string) => updateField('list', value)}>
            <SelectTrigger>
              <SelectValue placeholder="Select list" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="Main List">Main List</SelectItem>
              <SelectItem value="Test List">Test List</SelectItem>
              <SelectItem value="VIP List">VIP List</SelectItem>
            </SelectContent>
          </Select>
        </div>
      )}

      {formFields.field !== undefined && (
        <div className="space-y-2">
          <Label htmlFor="field">Field</Label>
          <Input
            id="field"
            value={formFields.field || ''}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateField('field', e.target.value)}
            placeholder="Field name"
          />
        </div>
      )}

      {formFields.value !== undefined && (
        <div className="space-y-2">
          <Label htmlFor="value">Value</Label>
          <Input
            id="value"
            value={formFields.value || ''}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateField('value', e.target.value)}
            placeholder="Field value"
          />
        </div>
      )}

      {formFields.reason !== undefined && (
        <div className="space-y-2">
          <Label htmlFor="reason">Reason</Label>
          <Input
            id="reason"
            value={formFields.reason || ''}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateField('reason', e.target.value)}
            placeholder="Reason for stopping"
          />
        </div>
      )}

      {formFields.action !== undefined && (
        <div className="space-y-2">
          <Label htmlFor="action">Action</Label>
          <Input
            id="action"
            value={formFields.action || ''}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateField('action', e.target.value)}
            placeholder="Action to perform"
          />
        </div>
      )}

      <DialogFooter>
        <Button variant="outline" onClick={onCancel}>
          Cancel
        </Button>
        <Button onClick={handleSave}>
          Save Changes
        </Button>
      </DialogFooter>
    </div>
  )
}
