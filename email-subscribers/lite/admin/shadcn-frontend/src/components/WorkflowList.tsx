import React, { useState, useEffect, useRef } from 'react';
import { Card, CardContent, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useNavigate } from 'react-router-dom';
import { 
  Search, 
  ChevronDown, 
  ArrowUpDown,
  Edit,
  Lightbulb,
  PencilLine,
  ShoppingCart,
  ShoppingBag,
  PartyPopper,
  Mail,
  ChevronRight,
  ChevronLeft
} from 'lucide-react';

interface Workflow {
  id: number;
  name: string;
  lastRanAt: string;
  openRate: string;
  clickRate: string;
  status: 'Active' | 'Not Active' | 'Draft';
  isActive: boolean;
}

const WorkflowList: React.FC = () => {
  const navigate = useNavigate();
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedList, setSelectedList] = useState('All');
  const [selectedStatus, setSelectedStatus] = useState('All');
  const [currentPage, setCurrentPage] = useState(1); // Start with page 1 as default
  const [sortBy, setSortBy] = useState<'lastRanAt' | null>(null);
  const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');
  const [showListDropdown, setShowListDropdown] = useState(false);
  const [showStatusDropdown, setShowStatusDropdown] = useState(false);
  const [selectedWorkflowIds, setSelectedWorkflowIds] = useState<Set<number>>(new Set());
  const [isAllSelected, setIsAllSelected] = useState(false);
  const listDropdownRef = useRef<HTMLDivElement>(null);
  const statusDropdownRef = useRef<HTMLDivElement>(null);
  
  const [allWorkflows, setAllWorkflows] = useState<Workflow[]>([
    // Page 1 workflows
    {
      id: 1,
      name: 'Subscriber: Confirmation email',
      lastRanAt: '5 min ago',
      openRate: '24%',
      clickRate: '12.5%',
      status: 'Not Active',
      isActive: false
    },
    {
      id: 2,
      name: 'Cart: Abandoned cart email with coupon',
      lastRanAt: '10 hrs ago',
      openRate: '40.8%',
      clickRate: '10.3%',
      status: 'Active',
      isActive: true
    },
    {
      id: 3,
      name: 'Send welcome email when someone subscribes',
      lastRanAt: '1 day ago',
      openRate: '0.0',
      clickRate: '0.0',
      status: 'Draft',
      isActive: false
    },
    {
      id: 4,
      name: 'Send confirmation email',
      lastRanAt: '3 days ago',
      openRate: '50.2%',
      clickRate: '15.8%',
      status: 'Not Active',
      isActive: false
    },
    {
      id: 5,
      name: 'Notify admin when someone subscribes',
      lastRanAt: '7 days ago',
      openRate: '38.7%',
      clickRate: '9.4%',
      status: 'Draft',
      isActive: false
    },
    // Page 2 workflows
    {
      id: 6,
      name: 'Welcome series for new customers',
      lastRanAt: '2 hours ago',
      openRate: '35.2%',
      clickRate: '18.7%',
      status: 'Active',
      isActive: true
    },
    {
      id: 7,
      name: 'Product recommendation email',
      lastRanAt: '5 hours ago',
      openRate: '28.5%',
      clickRate: '14.2%',
      status: 'Not Active',
      isActive: false
    },
    {
      id: 8,
      name: 'Birthday discount workflow',
      lastRanAt: '12 hours ago',
      openRate: '45.3%',
      clickRate: '22.1%',
      status: 'Active',
      isActive: true
    },
    {
      id: 9,
      name: 'Post-purchase follow-up',
      lastRanAt: '2 days ago',
      openRate: '32.8%',
      clickRate: '16.4%',
      status: 'Draft',
      isActive: false
    },
    {
      id: 10,
      name: 'Newsletter subscription confirmation',
      lastRanAt: '4 days ago',
      openRate: '41.7%',
      clickRate: '19.3%',
      status: 'Active',
      isActive: true
    },
    // Page 3 workflows
    {
      id: 11,
      name: 'Seasonal promotion campaign',
      lastRanAt: '1 hour ago',
      openRate: '52.1%',
      clickRate: '26.8%',
      status: 'Active',
      isActive: true
    },
    {
      id: 12,
      name: 'Feedback request workflow',
      lastRanAt: '6 hours ago',
      openRate: '29.4%',
      clickRate: '12.7%',
      status: 'Draft',
      isActive: false
    },
    {
      id: 13,
      name: 'Win-back campaign for inactive users',
      lastRanAt: '8 hours ago',
      openRate: '18.9%',
      clickRate: '8.5%',
      status: 'Not Active',
      isActive: false
    },
    {
      id: 14,
      name: 'VIP customer exclusive offers',
      lastRanAt: '1 day ago',
      openRate: '67.3%',
      clickRate: '34.2%',
      status: 'Active',
      isActive: true
    },
    {
      id: 15,
      name: 'Account verification reminder',
      lastRanAt: '3 days ago',
      openRate: '43.6%',
      clickRate: '21.8%',
      status: 'Draft',
      isActive: false
    }
  ]);

  // Close dropdowns when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (listDropdownRef.current && !listDropdownRef.current.contains(event.target as Node)) {
        setShowListDropdown(false);
      }
      if (statusDropdownRef.current && !statusDropdownRef.current.contains(event.target as Node)) {
        setShowStatusDropdown(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  // Filter and search workflows
  const filteredWorkflows = allWorkflows.filter(workflow => {
    // Search filter
    const matchesSearch = workflow.name.toLowerCase().includes(searchTerm.toLowerCase());
    
    // Status filter
    const matchesStatus = selectedStatus === 'All' || workflow.status === selectedStatus;
    
    // List filter (for now, we'll keep it simple - could be extended)
    const matchesList = selectedList === 'All' || true; // You can extend this logic
    
    return matchesSearch && matchesStatus && matchesList;
  });

  // Apply sorting
  const sortedWorkflows = [...filteredWorkflows];
  if (sortBy === 'lastRanAt') {
    sortedWorkflows.sort((a, b) => {
      // Convert time strings to comparable values for sorting
      const timeToMinutes = (timeStr: string) => {
        if (timeStr.includes('min ago')) {
          return parseInt(timeStr);
        } else if (timeStr.includes('hrs ago')) {
          return parseInt(timeStr) * 60;
        } else if (timeStr.includes('hour ago')) {
          return parseInt(timeStr) * 60;
        } else if (timeStr.includes('day ago') || timeStr.includes('days ago')) {
          return parseInt(timeStr) * 24 * 60;
        }
        return 0;
      };
      
      const aTime = timeToMinutes(a.lastRanAt);
      const bTime = timeToMinutes(b.lastRanAt);
      
      return sortOrder === 'asc' ? aTime - bTime : bTime - aTime;
    });
  }

  // Pagination
  const itemsPerPage = 5;
  const totalPages = Math.ceil(sortedWorkflows.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const paginatedWorkflows = sortedWorkflows.slice(startIndex, endIndex);

  // Interactive handlers
  const handleToggleWorkflow = (id: number) => {
    // Update the allWorkflows state by toggling the isActive property
    setAllWorkflows(prevWorkflows => 
      prevWorkflows.map(workflow => 
        workflow.id === id 
          ? { 
              ...workflow, 
              isActive: !workflow.isActive, 
              status: !workflow.isActive ? 'Active' : 'Not Active' as 'Active' | 'Not Active' | 'Draft'
            }
          : workflow
      )
    );
  };

  const handleSort = (field: 'lastRanAt') => {
    if (sortBy === field) {
      setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
    } else {
      setSortBy(field);
      setSortOrder('desc');
    }
  };

  const handlePageChange = (page: number) => {
    if (page >= 1 && page <= totalPages) {
      setCurrentPage(page);
    }
  };

  const handlePreviousPage = () => {
    if (currentPage > 1) {
      setCurrentPage(currentPage - 1);
    }
  };

  const handleNextPage = () => {
    if (currentPage < totalPages) {
      setCurrentPage(currentPage + 1);
    }
  };

  // Checkbox handlers
  const handleSelectAll = (checked: boolean) => {
    if (checked) {
      const allIds = new Set(paginatedWorkflows.map(workflow => workflow.id));
      setSelectedWorkflowIds(allIds);
      setIsAllSelected(true);
    } else {
      setSelectedWorkflowIds(new Set());
      setIsAllSelected(false);
    }
  };

  const handleSelectWorkflow = (id: number) => {
    const newSelected = new Set(selectedWorkflowIds);
    if (newSelected.has(id)) {
      newSelected.delete(id);
    } else {
      newSelected.add(id);
    }
    setSelectedWorkflowIds(newSelected);
    setIsAllSelected(newSelected.size === paginatedWorkflows.length && paginatedWorkflows.length > 0);
  };

  // Update isAllSelected when paginatedWorkflows changes
  useEffect(() => {
    const allCurrentIds = paginatedWorkflows.map(workflow => workflow.id);
    const allSelected = allCurrentIds.length > 0 && allCurrentIds.every(id => selectedWorkflowIds.has(id));
    setIsAllSelected(allSelected);
  }, [paginatedWorkflows, selectedWorkflowIds]);

  const listOptions = ['All', 'Active', 'Inactive'];
  const statusOptions = ['All', 'Active', 'Not Active', 'Draft'];

  const prebuiltWorkflows = [
    {
      title: 'Build Workflow From Scratch',
      icon: PencilLine,
      color: '#5e19cf',
      isPro: false
    },
    {
      title: 'Abandoned Cart',
      icon: ShoppingCart,
      color: '#0a0a0a',
      isPro: true
    },
    {
      title: 'Product Purchase',
      icon: ShoppingBag,
      color: '#0a0a0a',
      isPro: false
    },
    {
      title: 'Welcome Series',
      icon: PartyPopper,
      color: '#0a0a0a',
      isPro: false,
      emailCount: 3
    }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Active':
        return 'text-green-600';
      case 'Not Active':
        return 'text-red-600';
      case 'Draft':
        return 'text-blue-600';
      default:
        return 'text-gray-500';
    }
  };

  const getStatusIcon = (status: string) => {
    if (status === 'Active') {
      return (
        <div className="relative w-3.5 h-3.5">
          <div className="absolute inset-0 w-3.5 h-3.5 rounded-full bg-green-200"></div>
          <div className="absolute top-[3px] left-[3px] w-2 h-2 rounded-full bg-green-500"></div>
        </div>
      );
    } else if (status === 'Not Active') {
      return (
        <div className="relative w-3.5 h-3.5">
          <div className="absolute inset-0 w-3.5 h-3.5 rounded-full bg-red-200"></div>
          <div className="absolute top-[3px] left-[3px] w-2 h-2 rounded-full bg-red-500"></div>
        </div>
      );
    } else {
      return (
        <div className="relative w-3.5 h-3.5">
          <div className="absolute inset-0 w-3.5 h-3.5 rounded-full bg-blue-200"></div>
          <div className="absolute top-[3px] left-[3px] w-2 h-2 rounded-full bg-blue-600"></div>
        </div>
      );
    }
  };

  return (
    <div className="min-h-screen bg-neutral-50">
      <div className="container mx-auto px-4 sm:px-6 py-6 sm:py-10">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between mb-6 sm:mb-8 gap-4">
          <div className="flex flex-col gap-1">
            <h1 className="font-inter font-semibold text-xl sm:text-2xl leading-6 sm:leading-8 text-neutral-950">Workflows</h1>
            <p className="font-inter font-medium text-sm leading-5 text-neutral-500">
              Automate tasks at any stage. Set it up once, and let it run—simple, smart, and scalable.
            </p>
          </div>
          <Button
            onClick={() => navigate('/workflow-builder')}
            className="bg-[#5e19cf] hover:bg-[#5e19cf]/90 text-neutral-50 font-inter font-medium text-sm leading-5 px-4 py-2 h-9 rounded-lg shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] w-full sm:w-auto"
          >
            Create Workflow
          </Button>
        </div>

        {/* Preview Section */}
        <div className="mb-6 sm:mb-7">
          <Card className="bg-white border-[rgba(163,163,163,0.01)] rounded-xl">
            <CardContent className="p-3 sm:p-4">
              <div className="mb-3 sm:mb-4">
                <p className="text-sm sm:text-base font-normal text-neutral-950">
                  Preview a popular pre-built workflow
                </p>
              </div>
              
              {/* Divider */}
              <div className="h-px bg-gray-400 mb-3 sm:mb-4" />
              
              {/* Info Banner */}
              <div className="flex gap-2 mb-3 sm:mb-4">
                <Lightbulb className="w-5 h-5 sm:w-6 sm:h-6 text-neutral-500 flex-shrink-0" />
                <div className="text-sm sm:text-base font-medium text-neutral-500">
                  You're on the Free Plan. Enjoy a preview of pre-built customer workflows—
                  <span className="text-[#5e19cf]">upgrade</span> to launch them.
                </div>
              </div>
              
              {/* Workflow Templates */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
                {prebuiltWorkflows.map((workflow, index) => {
                  const IconComponent = workflow.icon;
                  return (
                    <Card key={index} className="bg-white border-neutral-200 rounded-[10px] relative">
                      <CardContent className="flex flex-col gap-3 sm:gap-4 px-3 sm:px-4 py-6 sm:py-8">
                        <div className="shrink-0 size-5 sm:size-6">
                          <IconComponent 
                            className="w-5 h-5 sm:w-6 sm:h-6" 
                            style={{ color: workflow.color }}
                          />
                        </div>
                        <div className="w-full">
                          <CardTitle 
                            className="text-sm sm:text-base font-medium leading-5 sm:leading-6"
                            style={{ color: workflow.title === 'Build Workflow From Scratch' ? '#5e19cf' : '#0a0a0a' }}
                          >
                            {workflow.title}
                          </CardTitle>
                        </div>
                        
                        {/* Pro Badge */}
                        {workflow.isPro && (
                          <div className="absolute top-3 right-3 sm:top-5 sm:right-5 bg-green-50 rounded-lg">
                            <div className="absolute border-[0.6px] border-green-700 border-solid inset-0 pointer-events-none rounded-lg" />
                            <div className="flex flex-row gap-2.5 px-2 py-1 sm:px-3">
                              <span className="text-xs font-medium text-green-700 leading-4">Pro</span>
                            </div>
                          </div>
                        )}
                        
                        {/* Email Count Badge */}
                        {workflow.emailCount && (
                          <div className="absolute top-3 right-3 sm:top-5 sm:right-5 bg-neutral-50 border border-neutral-200 px-2 py-1 sm:px-3 rounded-lg flex items-center gap-1">
                            <Mail className="w-3 h-3 text-neutral-950" />
                            <span className="text-xs font-medium text-neutral-950 leading-4">{workflow.emailCount}</span>
                          </div>
                        )}
                      </CardContent>
                    </Card>
                  );
                })}
              </div>
              
              {/* Browse Templates Button */}
              <div className="flex justify-center">
                <Button variant="ghost" className="flex items-center gap-1 px-2.5 py-1.5 h-8 sm:h-10 rounded-lg">
                  <span className="text-sm font-medium text-neutral-900">Browse all templates</span>
                  <ChevronRight className="w-4 h-4 sm:w-5 sm:h-5" />
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Recent Workflows Section */}
        <div className="flex flex-col gap-3">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <h2 className="text-lg font-semibold text-neutral-950">Recent Workflows</h2>
            <div className="text-sm text-neutral-500">Page {currentPage}</div>
          </div>
          
          <Card className="bg-white rounded-xl border-slate-200">
            <CardContent className="p-3 sm:p-4">
              {/* Search and Filter Controls */}
              <div className="flex flex-col lg:flex-row lg:items-center justify-between mb-4 gap-3 lg:gap-0">
                <div className="flex flex-col gap-2 w-full lg:w-[329px]">
                  <div className="relative">
                    <div className="absolute inset-y-0 left-3 flex items-center">
                      <Search className="w-4 h-4 text-neutral-500" />
                    </div>
                    <input
                      type="text"
                      placeholder="Search Workflows"
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      className="w-full h-9 pl-10 pr-3 bg-white border border-neutral-200 rounded-lg text-sm text-neutral-950 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#5e19cf] focus:border-transparent shadow-sm"
                    />
                  </div>
                </div>
                
                <div className="flex gap-1 flex-wrap sm:flex-nowrap">
                  {/* Lists Dropdown */}
                  <div className="relative flex-1 sm:flex-none" ref={listDropdownRef}>
                    <Button 
                      variant="secondary" 
                      className="bg-neutral-100 border border-neutral-200 px-3 sm:px-4 py-2 h-9 rounded-lg flex items-center gap-1 w-full sm:w-auto justify-between sm:justify-center"
                      onClick={() => setShowListDropdown(!showListDropdown)}
                    >
                      <span className="text-sm font-medium text-neutral-950">Lists : {selectedList}</span>
                      <ChevronDown className="w-3 h-3" />
                    </Button>
                    {showListDropdown && (
                      <div className="absolute top-full left-0 mt-1 w-full bg-white border border-neutral-200 rounded-lg shadow-lg z-10">
                        {listOptions.map((option) => (
                          <button
                            key={option}
                            className="w-full px-4 py-2 text-left text-sm hover:bg-neutral-50 first:rounded-t-lg last:rounded-b-lg"
                            onClick={() => {
                              setSelectedList(option);
                              setShowListDropdown(false);
                            }}
                          >
                            {option}
                          </button>
                        ))}
                      </div>
                    )}
                  </div>

                  {/* Status Dropdown */}
                  <div className="relative flex-1 sm:flex-none" ref={statusDropdownRef}>
                    <Button 
                      variant="secondary" 
                      className="bg-neutral-100 border border-neutral-200 px-3 sm:px-4 py-2 h-9 rounded-lg flex items-center gap-1 w-full sm:w-auto justify-between sm:justify-center"
                      onClick={() => setShowStatusDropdown(!showStatusDropdown)}
                    >
                      <span className="text-sm font-medium text-neutral-950">Status: {selectedStatus}</span>
                      <ChevronDown className="w-3 h-3" />
                    </Button>
                    {showStatusDropdown && (
                      <div className="absolute top-full left-0 mt-1 w-full bg-white border border-neutral-200 rounded-lg shadow-lg z-10">
                        {statusOptions.map((option) => (
                          <button
                            key={option}
                            className="w-full px-4 py-2 text-left text-sm hover:bg-neutral-50 first:rounded-t-lg last:rounded-b-lg"
                            onClick={() => {
                              setSelectedStatus(option);
                              setShowStatusDropdown(false);
                            }}
                          >
                            {option}
                          </button>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* Data Table */}
              <div className="border border-neutral-200 rounded-lg overflow-hidden">
                {/* Desktop Table Header - Hidden on mobile */}
                <div className="hidden sm:flex bg-neutral-100 border-b border-neutral-200 h-10 items-center">
                  <div className="w-8 px-3 flex items-center">
                    <Checkbox 
                      checked={isAllSelected}
                      onCheckedChange={handleSelectAll}
                      className="border-neutral-400 data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                    />
                  </div>
                  <div className="w-[364px] px-4 py-1.5">
                    <span className="text-xs font-medium text-neutral-500">Flow</span>
                  </div>
                  <div className="flex-1 px-4 py-2 min-w-[85px]">
                    <Button 
                      variant="ghost" 
                      className="flex items-center gap-2 p-0 h-auto"
                      onClick={() => handleSort('lastRanAt')}
                    >
                      <span className="text-sm font-medium text-neutral-500">Last ran at</span>
                      <ArrowUpDown className="w-4 h-4" />
                    </Button>
                  </div>
                  <div className="flex-1 px-4 py-1.5 min-w-[85px]">
                    <span className="text-xs font-medium text-neutral-500">Open Rate</span>
                  </div>
                  <div className="flex-1 px-4 py-1.5 min-w-[85px]">
                    <span className="text-xs font-medium text-neutral-500">Click Rate</span>
                  </div>
                  <div className="flex-1 px-4 py-1.5 min-w-[85px]">
                    <span className="text-xs font-medium text-neutral-500">Status</span>
                  </div>
                  <div className="w-[120px] px-4 py-1.5">
                    <span className="text-xs font-medium text-neutral-500">Action</span>
                  </div>
                </div>

                {/* Mobile Header - Visible only on mobile */}
                <div className="sm:hidden bg-neutral-100 border-b border-neutral-200 px-3 py-2 flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Checkbox 
                      checked={isAllSelected}
                      onCheckedChange={handleSelectAll}
                      className="border-neutral-400 data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                    />
                    <span className="text-xs font-medium text-neutral-500">Select All</span>
                  </div>
                  <Button 
                    variant="ghost" 
                    className="flex items-center gap-1 p-0 h-auto"
                    onClick={() => handleSort('lastRanAt')}
                  >
                    <span className="text-xs font-medium text-neutral-500">Sort</span>
                    <ArrowUpDown className="w-3 h-3" />
                  </Button>
                </div>

                {/* Table Rows */}
                {paginatedWorkflows.map((workflow: Workflow) => (
                  <div key={workflow.id} className="border-b border-neutral-200 last:border-b-0 hover:bg-neutral-50">
                    {/* Desktop Row Layout */}
                    <div className="hidden sm:flex items-center">
                      <div className="w-8 px-3 py-3 flex items-center">
                        <Checkbox 
                          checked={selectedWorkflowIds.has(workflow.id)}
                          onCheckedChange={() => handleSelectWorkflow(workflow.id)}
                          className="border-neutral-400 data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                        />
                      </div>
                      <div className="w-[364px] px-4 py-3">
                        <span className="text-sm font-normal text-neutral-950">{workflow.name}</span>
                      </div>
                      <div className="flex-1 px-4 py-3 min-w-[85px]">
                        <span className="text-sm font-normal text-neutral-950">{workflow.lastRanAt}</span>
                      </div>
                      <div className="flex-1 px-4 py-3 min-w-[85px]">
                        <span className="text-sm font-normal text-neutral-950">{workflow.openRate}</span>
                      </div>
                      <div className="flex-1 px-4 py-3 min-w-[85px]">
                        <span className="text-sm font-normal text-neutral-950">{workflow.clickRate}</span>
                      </div>
                      <div className="flex-1 px-4 py-3 min-w-[85px]">
                        <div className="flex items-center gap-3">
                          {getStatusIcon(workflow.status)}
                          <span className={`text-sm font-normal ${getStatusColor(workflow.status)}`}>
                            {workflow.status}
                          </span>
                        </div>
                      </div>
                      <div className="w-[120px] px-3 py-3 flex items-center gap-2.5">
                        {workflow.status === 'Draft' ? (
                          <Button variant="ghost" size="sm" className="p-2 h-8 w-8">
                            <Edit className="w-4 h-4 text-neutral-950" />
                          </Button>
                        ) : (
                          <div className="flex items-center">
                            <button 
                              onClick={() => handleToggleWorkflow(workflow.id)}
                              className={`rounded-full w-9 h-5 flex items-center px-0.5 transition-colors ${
                                workflow.isActive 
                                  ? 'bg-green-600 justify-end' 
                                  : 'bg-neutral-200 justify-start'
                              }`}
                            >
                              <div className="bg-white w-4 h-4 rounded-full shadow-lg" />
                            </button>
                          </div>
                        )}
                      </div>
                    </div>

                    {/* Mobile Row Layout */}
                    <div className="sm:hidden p-3">
                      <div className="flex items-start justify-between mb-2">
                        <div className="flex items-start gap-2 flex-1">
                          <Checkbox 
                            checked={selectedWorkflowIds.has(workflow.id)}
                            onCheckedChange={() => handleSelectWorkflow(workflow.id)}
                            className="border-neutral-400 data-[state=checked]:bg-primary data-[state=checked]:border-primary mt-0.5"
                          />
                          <div className="flex-1 min-w-0">
                            <h3 className="text-sm font-medium text-neutral-950 mb-1 pr-2">{workflow.name}</h3>
                            <div className="flex items-center gap-2 mb-2">
                              {getStatusIcon(workflow.status)}
                              <span className={`text-xs font-normal ${getStatusColor(workflow.status)}`}>
                                {workflow.status}
                              </span>
                            </div>
                          </div>
                        </div>
                        <div className="flex items-center gap-2">
                          {workflow.status === 'Draft' ? (
                            <Button variant="ghost" size="sm" className="p-1.5 h-7 w-7">
                              <Edit className="w-3 h-3 text-neutral-950" />
                            </Button>
                          ) : (
                            <button 
                              onClick={() => handleToggleWorkflow(workflow.id)}
                              className={`rounded-full w-8 h-4 flex items-center px-0.5 transition-colors ${
                                workflow.isActive 
                                  ? 'bg-green-600 justify-end' 
                                  : 'bg-neutral-200 justify-start'
                              }`}
                            >
                              <div className="bg-white w-3 h-3 rounded-full shadow-sm" />
                            </button>
                          )}
                        </div>
                      </div>
                      <div className="grid grid-cols-3 gap-2 text-xs">
                        <div>
                          <span className="text-neutral-500">Last ran:</span>
                          <p className="text-neutral-950 font-medium">{workflow.lastRanAt}</p>
                        </div>
                        <div>
                          <span className="text-neutral-500">Open Rate:</span>
                          <p className="text-neutral-950 font-medium">{workflow.openRate}</p>
                        </div>
                        <div>
                          <span className="text-neutral-500">Click Rate:</span>
                          <p className="text-neutral-950 font-medium">{workflow.clickRate}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              {/* Pagination - Position inside Recent workflows section */}
              <div className="w-full flex flex-col sm:flex-row justify-between items-center mt-4 gap-3">
                <div className="text-sm text-neutral-500 order-2 sm:order-1">
                  Showing {(currentPage - 1) * itemsPerPage + 1} to {Math.min(currentPage * itemsPerPage, sortedWorkflows.length)} of {sortedWorkflows.length} results
                </div>
                <div className="flex items-center gap-1 order-1 sm:order-2">
                  <Button 
                    variant="ghost" 
                    className="flex items-center gap-1 px-2 sm:px-2.5 py-2 h-8 sm:h-9 hover:bg-neutral-100 active:bg-neutral-100 focus:bg-neutral-100 disabled:hover:bg-transparent"
                    onClick={handlePreviousPage}
                    disabled={currentPage === 1}
                  >
                    <ChevronLeft className="w-3 h-3 sm:w-4 sm:h-4" />
                    <span className="text-xs sm:text-sm font-medium text-neutral-950 hidden sm:inline">Previous</span>
                  </Button>
                  <Button 
                    variant={currentPage === 1 ? "default" : "ghost"}
                    className={`w-7 h-7 sm:w-9 sm:h-9 p-0 text-xs sm:text-sm hover:bg-neutral-100 active:bg-neutral-100 focus:bg-neutral-100 ${
                      currentPage === 1 
                        ? 'bg-white border border-neutral-200 shadow-sm text-neutral-950' 
                        : ''
                    }`}
                    onClick={() => handlePageChange(1)}
                  >
                    <span className="font-medium text-neutral-950">1</span>
                  </Button>
                  <Button 
                    variant={currentPage === 2 ? "default" : "ghost"}
                    className={`w-7 h-7 sm:w-9 sm:h-9 p-0 text-xs sm:text-sm hover:bg-neutral-100 active:bg-neutral-100 focus:bg-neutral-100 ${
                      currentPage === 2 
                        ? 'bg-white border border-neutral-200 shadow-sm text-neutral-950' 
                        : ''
                    }`}
                    onClick={() => handlePageChange(2)}
                  >
                    <span className="font-medium text-neutral-950">2</span>
                  </Button>
                  <Button 
                    variant={currentPage === 3 ? "default" : "ghost"}
                    className={`w-7 h-7 sm:w-9 sm:h-9 p-0 text-xs sm:text-sm hover:bg-neutral-100 active:bg-neutral-100 focus:bg-neutral-100 ${
                      currentPage === 3 
                        ? 'bg-white border border-neutral-200 shadow-sm text-neutral-950' 
                        : ''
                    }`}
                    onClick={() => handlePageChange(3)}
                  >
                    <span className="font-medium text-neutral-950">3</span>
                  </Button>
                  <Button variant="ghost" className="p-1 sm:p-2.5 w-7 h-7 sm:w-9 sm:h-9 hover:bg-neutral-100 active:bg-neutral-100 focus:bg-neutral-100">
                    <span className="text-xs sm:text-sm font-medium text-neutral-950">...</span>
                  </Button>
                  <Button 
                    variant="secondary" 
                    className="bg-neutral-100 flex items-center gap-1 px-2 sm:px-4 py-2 h-8 sm:h-9 hover:bg-neutral-200 active:bg-neutral-200 focus:bg-neutral-200 disabled:hover:bg-neutral-100"
                    onClick={handleNextPage}
                    disabled={currentPage >= totalPages}
                  >
                    <span className="text-xs sm:text-sm font-medium text-neutral-950 hidden sm:inline">Next</span>
                    <ChevronRight className="w-3 h-3 sm:w-4 sm:h-4" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
};

export default WorkflowList;
