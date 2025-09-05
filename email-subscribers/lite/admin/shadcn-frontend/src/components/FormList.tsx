import React, { useState, useMemo } from 'react';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Card, CardContent } from './ui/card';
import { Search } from 'lucide-react';
import CreateFormSection from './CreateFormSection';

// Import icons
import {
  ellipsisIconFigma,
  formsTutorialBg,
  formsTutorialOverlay,
  playIconWhiteFigma,
} from '../assets/images';

interface FormData {
  name: string;
  date: string;
  subscribers: number;
  shortCodeId: number;
  audience: {
    main: string;
    extra: string;
  };
}

const formsData: FormData[] = [
  { 
    name: "Subscription Form with GDPR consent", 
    date: "Last edited on 12/03/2025 2:04 PM",
    subscribers: 10,
    shortCodeId: 1,
    audience: { main: "Test, Customers", extra: "+2" }
  },
  { 
    name: "Newsletter Signup", 
    date: "Last edited on 12/03/2025 2:04 PM",
    subscribers: 15,
    shortCodeId: 2,
    audience: { main: "Sample, Squad", extra: "2+" }
  },
  { 
    name: "Minimal subscription form with selection of list", 
    date: "Last edited on 12/03/2025 2:04 PM",
    subscribers: 20,
    shortCodeId: 3,
    audience: { main: "Example, Crew", extra: "3+" }
  },
  { 
    name: "Subscription Form", 
    date: "Last edited on 12/03/2025 2:04 PM",
    subscribers: 20,
    shortCodeId: 4,
    audience: { main: "Demo, Group", extra: "1+" }
  },
  { 
    name: "Subscription Form For Update", 
    date: "Last edited on 12/03/2025 2:04 PM",
    subscribers: 50,
    shortCodeId: 5,
    audience: { main: "Trial, Unit", extra: "0+" }
  },
  { 
    name: "Contact Form with File Upload", 
    date: "Last edited on 11/03/2025 1:30 PM",
    subscribers: 35,
    shortCodeId: 6,
    audience: { main: "Business, Leads", extra: "+5" }
  },
  { 
    name: "Event Registration Form", 
    date: "Last edited on 10/03/2025 3:15 PM",
    subscribers: 80,
    shortCodeId: 7,
    audience: { main: "Events, Users", extra: "+12" }
  },
  { 
    name: "Product Feedback Survey", 
    date: "Last edited on 09/03/2025 11:45 AM",
    subscribers: 25,
    shortCodeId: 8,
    audience: { main: "Feedback, Team", extra: "+3" }
  },
  { 
    name: "Lead Generation Form", 
    date: "Last edited on 08/03/2025 4:20 PM",
    subscribers: 45,
    shortCodeId: 9,
    audience: { main: "Sales, Prospects", extra: "+8" }
  },
  { 
    name: "Email Preferences Update", 
    date: "Last edited on 07/03/2025 9:10 AM",
    subscribers: 12,
    shortCodeId: 10,
    audience: { main: "Settings, Users", extra: "+1" }
  },
  { 
    name: "Multi-step Registration Form", 
    date: "Last edited on 06/03/2025 2:45 PM",
    subscribers: 60,
    shortCodeId: 11,
    audience: { main: "Register, Members", extra: "+15" }
  },
  { 
    name: "Quick Survey Form", 
    date: "Last edited on 05/03/2025 10:30 AM",
    subscribers: 18,
    shortCodeId: 12,
    audience: { main: "Survey, Users", extra: "+2" }
  },
  { 
    name: "Partner Application Form", 
    date: "Last edited on 04/03/2025 1:15 PM",
    subscribers: 8,
    shortCodeId: 13,
    audience: { main: "Partners, Apps", extra: "+0" }
  },
  { 
    name: "Job Application Form", 
    date: "Last edited on 03/03/2025 3:50 PM",
    subscribers: 22,
    shortCodeId: 14,
    audience: { main: "Careers, Team", extra: "+4" }
  },
  { 
    name: "Customer Support Form", 
    date: "Last edited on 02/03/2025 11:20 AM",
    subscribers: 16,
    shortCodeId: 15,
    audience: { main: "Support, Cases", extra: "+1" }
  },
  { 
    name: "Product Demo Request", 
    date: "Last edited on 01/03/2025 4:35 PM",
    subscribers: 38,
    shortCodeId: 16,
    audience: { main: "Demo, Requests", extra: "+6" }
  },
  { 
    name: "Blog Subscription Form", 
    date: "Last edited on 28/02/2025 9:45 AM",
    subscribers: 55,
    shortCodeId: 17,
    audience: { main: "Blog, Readers", extra: "+10" }
  },
  { 
    name: "Webinar Registration", 
    date: "Last edited on 27/02/2025 2:15 PM",
    subscribers: 42,
    shortCodeId: 18,
    audience: { main: "Webinar, Attendees", extra: "+7" }
  },
  { 
    name: "Free Trial Signup", 
    date: "Last edited on 26/02/2025 10:30 AM",
    subscribers: 68,
    shortCodeId: 19,
    audience: { main: "Trial, Users", extra: "+14" }
  },
  { 
    name: "Beta Testing Application", 
    date: "Last edited on 25/02/2025 3:20 PM",
    subscribers: 29,
    shortCodeId: 20,
    audience: { main: "Beta, Testers", extra: "+5" }
  },
  { 
    name: "Affiliate Program Form", 
    date: "Last edited on 24/02/2025 1:10 PM",
    subscribers: 14,
    shortCodeId: 21,
    audience: { main: "Affiliate, Partners", extra: "+2" }
  },
  { 
    name: "Consultation Request", 
    date: "Last edited on 23/02/2025 11:50 AM",
    subscribers: 31,
    shortCodeId: 22,
    audience: { main: "Consultation, Leads", extra: "+4" }
  },
  { 
    name: "Newsletter Unsubscribe", 
    date: "Last edited on 22/02/2025 4:25 PM",
    subscribers: 7,
    shortCodeId: 23,
    audience: { main: "Unsubscribe, Users", extra: "+0" }
  },
  { 
    name: "Custom Quote Request", 
    date: "Last edited on 21/02/2025 9:15 AM",
    subscribers: 26,
    shortCodeId: 24,
    audience: { main: "Quote, Requests", extra: "+3" }
  },
  { 
    name: "Workshop Registration", 
    date: "Last edited on 20/02/2025 2:40 PM",
    subscribers: 44,
    shortCodeId: 25,
    audience: { main: "Workshop, Participants", extra: "+8" }
  },
];

const FormList: React.FC = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [forms, setForms] = useState<FormData[]>(formsData); // Use actual forms data
  const itemsPerPage = 10;

  // Filter forms based on search term
  const filteredForms = useMemo(() => {
    return forms.filter(form =>
      form.name.toLowerCase().includes(searchTerm.toLowerCase())
    );
  }, [searchTerm, forms]);

  // Calculate pagination
  const totalPages = Math.ceil(filteredForms.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const currentForms = filteredForms.slice(startIndex, endIndex);

  // Reset to first page when search changes
  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setSearchTerm(e.target.value);
    setCurrentPage(1);
  };

  // Handle clear filters functionality
  const handleClearFilters = () => {
    setSearchTerm('');
    setCurrentPage(1);
  };

  // Handle delete form functionality
  const handleDeleteForm = (formIndex: number) => {
    const globalIndex = startIndex + formIndex;
    const formToDelete = filteredForms[globalIndex];
    
    // Remove the form from the forms array
    setForms(prevForms => prevForms.filter(form => form.shortCodeId !== formToDelete.shortCodeId));
    
    // If we're on the last page and it becomes empty, go to previous page
    const newFilteredCount = filteredForms.length - 1;
    const newTotalPages = Math.ceil(newFilteredCount / itemsPerPage);
    if (currentPage > newTotalPages && newTotalPages > 0) {
      setCurrentPage(newTotalPages);
    }
  };

  // Handle copy short code functionality
  const handleCopyShortCode = async (shortCodeId: number) => {
    const textToCopy = `id="${shortCodeId}"`;
    
    try {
      await navigator.clipboard.writeText(textToCopy);
      // You could add a toast notification here if desired
      console.log(`Copied to clipboard: ${textToCopy}`);
    } catch (err) {
      // Fallback for older browsers
      const textArea = document.createElement('textarea');
      textArea.value = textToCopy;
      document.body.appendChild(textArea);
      textArea.focus();
      textArea.select();
      
      try {
        document.execCommand('copy');
        console.log(`Copied to clipboard: ${textToCopy}`);
      } catch (fallbackErr) {
        console.error('Failed to copy text: ', fallbackErr);
      }
      
      document.body.removeChild(textArea);
    }
  };

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
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

  // Generate page numbers for pagination
  const getPageNumbers = () => {
    const pages = [];
    const showPages = 3; // Show 3 page numbers at most
    
    if (totalPages <= showPages) {
      for (let i = 1; i <= totalPages; i++) {
        pages.push(i);
      }
    } else {
      if (currentPage <= 2) {
        pages.push(1, 2, 3);
      } else if (currentPage >= totalPages - 1) {
        pages.push(totalPages - 2, totalPages - 1, totalPages);
      } else {
        pages.push(currentPage - 1, currentPage, currentPage + 1);
      }
    }
    
    return pages;
  };

  return (
    <div className="px-6 py-10 space-y-8 w-full max-w-none">
      {/* Header Section */}
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div className="space-y-1">
            <h1 className="text-2xl font-semibold leading-8 text-neutral-950">Forms</h1>
            <p className="text-sm font-medium leading-5 text-neutral-500">
              Forms are interactive tools that collect user input, helping you capture data, receive feedback, and streamline communication
            </p>
          </div>
          <Button className="bg-[#5e19cf] hover:bg-[#5e19cf]/90 text-neutral-50 h-9 px-4 py-2 rounded-lg shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)]">
            <span className="font-medium text-[14px] leading-[20px]">Create Form</span>
          </Button>
        </div>

        {/* Start creating forms and How to create Forms sections */}
        <div className="space-y-4">
          <h2 className="text-lg font-semibold leading-7 text-black">Start creating forms</h2>
          
          <div className="flex gap-4">
            {/* Left side - Popular forms grid */}
            <div className="flex-1">
              <CreateFormSection />
            </div>
            
            {/* Right side - How to create Forms tutorial */}
            <div className="w-[348px]">
              <div className="bg-white box-border content-stretch flex flex-col gap-2 items-start justify-start overflow-clip p-2.5 relative rounded-xl border border-slate-200 h-full">
                <div className="box-border content-stretch flex flex-col gap-2 items-start justify-start p-0 relative shrink-0 w-full">
                  <div className="aspect-[2/1] bg-[0%_71.82%,_0%_50%] bg-no-repeat bg-size-[100%_300%,100%_123.76%] rounded-[3.135px] shrink-0 w-full relative overflow-hidden"
                    style={{
                      backgroundImage: `url('${formsTutorialBg}'), url('${formsTutorialOverlay}')`,
                    }}
                  >
                    {/* Play Button Overlay */}
                    <div className="absolute backdrop-blur-[24.821px] backdrop-filter bg-[rgba(0,0,0,0.24)] box-border content-stretch flex flex-row gap-[8.709px] items-center justify-center left-1/2 p-[6.967px] rounded-[55.556px] top-1/2 translate-x-[-50%] translate-y-[-50%] cursor-pointer hover:bg-[rgba(0,0,0,0.3)] transition-colors">
                      <div className="overflow-clip relative shrink-0 size-[22px]">
                        <div className="absolute bottom-[12.5%] left-1/4 right-[16.667%] top-[12.5%]">
                          <div className="absolute bottom-[-5.556%] left-[-7.143%] right-[-7.143%] top-[-5.556%]">
                            <img 
                              alt="Play video" 
                              className="block max-w-none size-full" 
                              src={playIconWhiteFigma} 
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="box-border content-stretch flex flex-row gap-2 items-start justify-start px-2.5 py-0 relative shrink-0 w-full">
                  <div className="basis-0 box-border content-stretch flex flex-col gap-1.5 grow items-start justify-start leading-[0] min-h-px min-w-px not-italic p-0 relative shrink-0 text-left">
                    <div className="font-['Inter'] font-semibold relative shrink-0 text-[14.11px] text-neutral-950 w-full">
                      <p className="block leading-[21.947px]">How to create Forms?</p>
                    </div>
                    <div className="font-['Inter'] font-medium relative shrink-0 text-[10.97px] text-neutral-500 w-full">
                      <p className="block leading-[15.676px]">
                        Learn how to create, customize, and manage forms effortlessly with our step-by-step tutorials
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* All Forms Section */}
        <div className="space-y-3">
          <h2 className="text-lg font-semibold leading-7 text-neutral-950">All Forms</h2>
          
          <Card className="bg-white border border-slate-200 rounded-xl">
            <CardContent className="p-4 space-y-4">
              {/* Search and Filters */}
              <div className="flex items-center justify-between">
                <div className="relative w-[329px]">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-neutral-500" />
                  <Input
                    placeholder="Search Forms"
                    className="pl-10 h-9 border-neutral-200 bg-white text-sm"
                    value={searchTerm}
                    onChange={handleSearchChange}
                  />
                </div>
                
                <div className="flex items-center gap-1">
                  <div className="bg-neutral-100 border border-neutral-200 flex items-center justify-center gap-1 h-9 px-4 py-2 rounded-lg">
                    <span className="font-medium text-[14px] leading-[20px] text-neutral-950 whitespace-pre">Type: All </span>
                    <div className="overflow-hidden relative shrink-0 size-3">
                      <div className="relative size-full">
                        <div className="absolute bottom-[37.5%] left-1/4 right-1/4 top-[37.5%]">
                          <div className="absolute bottom-[-16.667%] left-[-8.333%] right-[-8.333%] top-[-16.667%]">
                            <img alt="" className="block max-w-none size-full" src="/src/assets/images/chevron-down-figma.svg" />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="bg-neutral-100 border border-neutral-200 flex items-center justify-center gap-1 h-9 px-4 py-2 rounded-lg">
                    <span className="font-medium text-[14px] leading-[20px] text-neutral-950">Sort: Last edited</span>
                    <div className="overflow-hidden relative shrink-0 size-3">
                      <div className="relative size-full">
                        <div className="absolute bottom-[37.5%] left-1/4 right-1/4 top-[37.5%]">
                          <div className="absolute bottom-[-16.667%] left-[-8.333%] right-[-8.333%] top-[-16.667%]">
                            <img alt="" className="block max-w-none size-full" src="/src/assets/images/chevron-down-figma.svg" />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <button 
                    onClick={handleClearFilters}
                    className="box-border content-stretch flex flex-row gap-1 h-9 items-center justify-center px-4 py-2 relative rounded-lg shrink-0 hover:bg-gray-50 transition-colors cursor-pointer"
                  >
                    <div className="flex flex-col font-medium justify-center leading-[0] not-italic relative shrink-0 text-[#5e19cf] text-[14px] text-left text-nowrap">
                      <p className="block leading-[20px] whitespace-pre">Clear filters</p>
                    </div>
                  </button>
                </div>
              </div>

              {/* Data Table or Empty State */}
              <div className="flex flex-col">
                {filteredForms.length > 0 ? (
                  <>
                    {/* Data Table */}
                    <div className="box-border content-stretch flex flex-row items-start justify-start overflow-clip p-0 relative rounded-lg shrink-0 w-full">
                      {/* Form Name Column */}
                      <div className="basis-0 box-border content-stretch flex flex-col grow items-start justify-start min-h-px min-w-px overflow-clip p-0 relative shrink-0">
                        {/* Header */}
                        <div className="bg-neutral-100 box-border content-stretch flex flex-row h-10 items-center justify-between min-w-[85px] px-4 py-1.5 relative rounded-bl-[8px] rounded-tl-[8px] shrink-0 w-full">
                          <div className="basis-0 flex flex-col font-['Inter'] font-medium grow h-full justify-center leading-[0] min-h-px min-w-px not-italic relative shrink-0 text-[12px] text-left text-neutral-500">
                            <p className="block leading-[16px]">Form Name</p>
                          </div>
                        </div>
                        
                        {/* Form Rows */}
                        {currentForms.map((form, index) => (
                          <div key={index} className="box-border content-stretch flex flex-row gap-2 h-[78.2px] items-center justify-start min-w-[85px] px-4 py-2 relative shrink-0 w-full">
                            <div className="absolute border-[0px_0px_1px] border-neutral-200 border-solid inset-0 pointer-events-none" />
                            <div className="basis-0 box-border content-stretch flex flex-col gap-1 grow items-start justify-start leading-[0] min-h-px min-w-px not-italic p-0 relative shrink-0 text-left text-nowrap">
                              <div className="flex flex-col font-['Inter'] font-medium justify-center overflow-ellipsis overflow-hidden relative shrink-0 text-[14px] text-neutral-950 w-full">
                                <p className="[text-overflow:inherit] [text-wrap-mode:inherit] [white-space-collapse:inherit] block leading-[20px] overflow-inherit">
                                  {form.name}
                                </p>
                              </div>
                              <div className="flex flex-col font-['Inter'] font-normal justify-center overflow-ellipsis overflow-hidden relative shrink-0 text-[12px] text-neutral-500 w-full">
                                <p className="[text-overflow:inherit] [text-wrap-mode:inherit] [white-space-collapse:inherit] block leading-[16px] overflow-inherit">
                                  {form.date}
                                </p>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>

                      {/* Subscribers Column */}
                      <div className="box-border content-stretch flex flex-col items-start justify-start overflow-clip p-0 relative shrink-0 w-[175.25px]">
                        <div className="bg-neutral-100 box-border content-stretch flex flex-row gap-2.5 h-10 items-center justify-start p-0 relative shrink-0 w-full">
                          <div className="bg-[rgba(255,255,255,0)] box-border content-stretch flex flex-row gap-2 h-9 items-center justify-center px-4 py-2 relative rounded-lg shrink-0">
                            <div className="flex flex-col font-['Inter'] font-medium justify-center leading-[0] not-italic relative shrink-0 text-[14px] text-left text-neutral-500 text-nowrap">
                              <p className="block leading-[20px] whitespace-pre">Subscribers</p>
                            </div>
                          </div>
                        </div>
                        
                        {currentForms.map((form, index) => (
                          <div key={index} className="box-border content-stretch flex flex-row gap-3 h-[78.2px] items-center justify-start min-w-[85px] px-4 py-2 relative shrink-0 w-full">
                            <div className="absolute border-[0px_0px_1px] border-neutral-200 border-solid inset-0 pointer-events-none" />
                            <div className="box-border content-stretch flex flex-row gap-2.5 items-center justify-start px-5 py-2 relative rounded-xl shrink-0 border border-solid border-zinc-200">
                              <div className="flex flex-col font-['Inter'] font-normal justify-center leading-[0] not-italic relative shrink-0 text-[14px] text-left text-neutral-950 text-nowrap">
                                <p className="block leading-[20px] whitespace-pre">{form.subscribers}</p>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>

                      {/* Short Code Column */}
                      <div className="basis-0 box-border content-stretch flex flex-col grow items-start justify-start min-h-px min-w-px overflow-clip p-0 relative shrink-0">
                        <div className="bg-neutral-100 box-border content-stretch flex flex-row h-10 items-center justify-between min-w-[85px] px-4 py-1.5 relative shrink-0 w-full">
                          <div className="basis-0 flex flex-col font-['Inter'] font-medium grow h-full justify-center leading-[0] min-h-px min-w-px not-italic relative shrink-0 text-[12px] text-left text-neutral-500">
                            <p className="block leading-[16px]">Short Code</p>
                          </div>
                        </div>
                        
                        {currentForms.map((form, index) => (
                          <div key={index} className="box-border content-stretch flex flex-row gap-3 h-[78.2px] items-center justify-start min-w-[85px] px-4 py-2 relative shrink-0 w-full">
                            <div className="absolute border-[0px_0px_1px] border-neutral-200 border-solid inset-0 pointer-events-none" />
                            <div className="bg-zinc-100 box-border content-stretch flex flex-row gap-2.5 items-center justify-start px-5 py-2 relative rounded-xl shrink-0">
                              <div className="flex flex-col font-['Inter'] font-normal justify-center leading-[0] not-italic relative shrink-0 text-[14px] text-left text-neutral-950 text-nowrap">
                                <p className="block leading-[20px] whitespace-pre">{`id="${form.shortCodeId}"`}</p>
                              </div>
                              <div 
                                className="overflow-clip relative shrink-0 size-5 cursor-pointer hover:bg-gray-200 rounded p-1 transition-colors"
                                onClick={() => handleCopyShortCode(form.shortCodeId)}
                                title={`Copy ${`id="${form.shortCodeId}"`}`}
                              >
                                <div className="absolute inset-[8.333%]">
                                  <div className="absolute inset-[-6%]">
                                    <img alt="" className="block max-w-none size-full" src="/src/assets/images/copy-icon-figma.svg" />
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>

                      {/* Audience Column */}
                      <div className="basis-0 box-border content-stretch flex flex-col grow items-start justify-start min-h-px min-w-px overflow-clip p-0 relative shrink-0">
                        <div className="bg-neutral-100 box-border content-stretch flex flex-row h-10 items-center justify-between min-w-[85px] px-4 py-1.5 relative shrink-0 w-full">
                          <div className="basis-0 flex flex-col font-['Inter'] font-medium grow h-full justify-center leading-[0] min-h-px min-w-px not-italic relative shrink-0 text-[12px] text-left text-neutral-500">
                            <p className="block leading-[16px]">Audience</p>
                          </div>
                        </div>
                        
                        {currentForms.map((form, index) => (
                          <div key={index} className="box-border content-stretch flex flex-row gap-3 h-[78.2px] items-center justify-start min-w-[85px] px-4 py-2 relative shrink-0 w-full">
                            <div className="absolute border-[0px_0px_1px] border-neutral-200 border-solid inset-0 pointer-events-none" />
                            <div className="basis-0 box-border content-stretch flex flex-row gap-2.5 grow items-center justify-start min-h-px min-w-px p-0 relative shrink-0">
                              <div className="basis-0 flex flex-col font-['Inter'] font-normal grow justify-center leading-[0] min-h-px min-w-px not-italic relative shrink-0 text-[0px] text-left text-neutral-950">
                                <p>
                                  <span className="leading-[20px] text-[14px]">{form.audience.main} </span>
                                  <span className="font-['Inter'] font-medium leading-[16px] not-italic text-[12px] text-neutral-400">{form.audience.extra}</span>
                                </p>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>

                      {/* Actions Column */}
                      <div className="box-border content-stretch flex flex-col items-start justify-start overflow-clip p-0 relative shrink-0">
                        <div className="bg-neutral-100 box-border content-stretch flex flex-row gap-[102px] h-10 items-center justify-start min-w-[85px] px-4 py-1.5 relative rounded-br-[8px] rounded-tr-[8px] shrink-0 w-[136px]">
                          <div className="basis-0 flex flex-col font-['Inter'] font-medium grow h-full justify-center leading-[0] min-h-px min-w-px not-italic relative shrink-0 text-[12px] text-left text-neutral-500">
                            <p className="block leading-[16px]">Actions</p>
                          </div>
                        </div>
                        
                        {currentForms.map((_, index) => (
                          <div key={index} className="box-border content-stretch flex flex-row gap-2.5 h-[78.2px] items-center justify-start pl-3 pr-2 py-2 relative shrink-0 w-full">
                            <div className="absolute border-[0px_0px_1px] border-neutral-200 border-solid inset-0 pointer-events-none" />
                            <div className="box-border content-stretch flex flex-row gap-2.5 items-center justify-center p-[10px] relative shrink-0 size-8">
                              <div className="overflow-clip relative shrink-0 size-4">
                                <div className="absolute bottom-[16.667%] left-[12.504%] right-[12.5%] top-[12.501%]">
                                  <div className="absolute bottom-[-5.868%] left-[-5.542%] right-[-5.542%] top-[-5.868%]">
                                    <img alt="" className="block max-w-none size-full" src="/src/assets/images/edit-icon-figma.svg" />
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div 
                              className="box-border content-stretch flex flex-row gap-2.5 items-center justify-center p-[10px] relative shrink-0 size-8 cursor-pointer hover:bg-gray-100 rounded"
                              onClick={() => handleDeleteForm(index)}
                            >
                              <div className="overflow-clip relative shrink-0 size-4">
                                <div className="absolute bottom-[8.333%] left-[12.5%] right-[12.5%] top-[8.333%]">
                                  <div className="absolute bottom-[-4.987%] left-[-5.542%] right-[-5.542%] top-[-4.987%]">
                                    <img alt="" className="block max-w-none size-full" src="/src/assets/images/delete-icon-figma.svg" />
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div className="box-border content-stretch flex flex-row gap-2.5 items-center justify-center p-[10px] relative shrink-0 size-8">
                              <div className="overflow-clip relative shrink-0 size-4">
                                <div className="absolute bottom-[8.333%] left-[12.5%] right-[12.5%] top-[8.333%]">
                                  <div className="absolute bottom-[-4.987%] left-[-5.542%] right-[-5.542%] top-[-4.987%]">
                                    <img alt="" className="block max-w-none size-full" src="/src/assets/images/file-stack-icon-figma.svg" />
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                    
                    {/* Pagination - Only show if there are results and multiple pages */}
                    {totalPages > 1 && (
                      <div className="box-border content-stretch flex flex-row gap-1 items-center justify-end p-0 relative shrink-0 mt-4">
                        {/* Previous Button */}
                        <button
                          onClick={handlePreviousPage}
                          disabled={currentPage === 1}
                          className={`bg-[rgba(255,255,255,0)] box-border content-stretch flex flex-row gap-1 h-9 items-center justify-center pl-2.5 pr-4 py-2 relative rounded-lg shrink-0 ${
                            currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:bg-gray-50'
                          }`}
                        >
                          <div className="overflow-clip relative shrink-0 size-4">
                            <div className="absolute bottom-1/4 left-[37.5%] right-[37.5%] top-1/4">
                              <div className="absolute bottom-[-8.313%] left-[-16.625%] right-[-16.625%] top-[-8.313%]">
                                <img alt="" className="block max-w-none size-full" src="/src/assets/images/chevron-left-figma.svg" />
                              </div>
                            </div>
                          </div>
                          <div className="flex flex-col font-['Inter'] font-medium justify-center leading-[0] not-italic relative shrink-0 text-[14px] text-left text-neutral-950 text-nowrap">
                            <p className="block leading-[20px] whitespace-pre">Previous</p>
                          </div>
                        </button>

                        {/* Page Numbers */}
                        {getPageNumbers().map((pageNum) => (
                          <button
                            key={pageNum}
                            onClick={() => handlePageChange(pageNum)}
                            className={`box-border content-stretch flex flex-row items-center justify-center p-0 relative rounded-lg shrink-0 size-9 cursor-pointer ${
                              currentPage === pageNum
                                ? 'bg-white border border-neutral-200 shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)]'
                                : 'bg-[rgba(255,255,255,0)] hover:bg-gray-50'
                            }`}
                          >
                            <div className="flex flex-col font-['Inter'] font-medium justify-center leading-[0] not-italic relative shrink-0 text-[14px] text-left text-neutral-950 text-nowrap">
                              <p className="block leading-[20px] whitespace-pre">{pageNum}</p>
                            </div>
                          </button>
                        ))}

                        {/* Ellipsis (show if there are more pages beyond visible range) */}
                        {totalPages > 3 && currentPage < totalPages - 1 && (
                          <div className="box-border content-stretch flex flex-row gap-2.5 items-center justify-start p-[10px] relative shrink-0 size-9">
                            <div className="overflow-clip relative shrink-0 size-4">
                              <div className="absolute bottom-[45.833%] left-[16.667%] right-[16.667%] top-[45.833%]">
                                <div className="absolute bottom-[-49.875%] left-[-6.234%] right-[-6.234%] top-[-49.875%]">
                                  <img alt="" className="block max-w-none size-full" src={ellipsisIconFigma} />
                                </div>
                              </div>
                            </div>
                          </div>
                        )}

                        {/* Next Button */}
                        <button
                          onClick={handleNextPage}
                          disabled={currentPage === totalPages}
                          className={`box-border content-stretch flex flex-row gap-1 h-9 items-center justify-center pl-4 pr-2.5 py-2 relative rounded-lg shrink-0 ${
                            currentPage === totalPages 
                              ? 'bg-neutral-100 opacity-50 cursor-not-allowed' 
                              : 'bg-neutral-100 cursor-pointer hover:bg-neutral-200'
                          }`}
                        >
                          <div className="flex flex-col font-['Inter'] font-medium justify-center leading-[0] not-italic relative shrink-0 text-[14px] text-left text-neutral-900 text-nowrap">
                            <p className="block leading-[20px] whitespace-pre">Next</p>
                          </div>
                          <div className="overflow-clip relative shrink-0 size-4">
                            <div className="absolute bottom-1/4 left-[37.5%] right-[37.5%] top-1/4">
                              <div className="absolute bottom-[-8.313%] left-[-16.625%] right-[-16.625%] top-[-8.313%]">
                                <img alt="" className="block max-w-none size-full" src="/src/assets/images/chevron-right-figma.svg" />
                              </div>
                            </div>
                          </div>
                        </button>
                      </div>
                    )}
                  </>
                ) : (
                  /* Empty State - No Forms data found */
                  <div className="flex flex-col items-center justify-center py-16 px-4 text-center">
                    <h3 className="text-lg font-semibold text-neutral-950">No Forms data found</h3>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
};

export default FormList;
