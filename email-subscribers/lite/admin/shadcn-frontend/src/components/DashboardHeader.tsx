import { chevronDownOnboarding } from "@/assets/images";
import { useState } from "react";
import { getBaseUrl } from "../api/client";

export default function DashboardHeader() {
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  
  const currentDate = new Date().toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  // Get dynamic greeting based on time
  const getTimeBasedGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) {
      return "Good morning";
    } else if (hour < 18) {
      return "Good afternoon";
    } else {
      return "Good evening";
    }
  };

  // Get current user name from WordPress admin data
  const getCurrentUserName = () => {
    const adminData = (window as any).icegramExpressAdminData;
    if (adminData?.currentUser) {
      // Use display name if available, fallback to first name, then generic greeting
      return adminData.currentUser.displayName || 
             adminData.currentUser.firstName || 
             "there";
    }
    return "there";
  };

  const createNewOptions = [
    { label: "New Broadcast", href: `${getBaseUrl()}/wp-admin/admin.php?page=es_campaigns#!/gallery?campaignType=newsletter` },
    { label: "New Post Notification", href: `${getBaseUrl()}/wp-admin/admin.php?page=es_campaigns#!/gallery?campaignType=post_notification` },
    { label: "New Sequence", href: `${getBaseUrl()}/wp-admin/admin.php?page=es_sequence&action=new` },
    { label: "New Template", href: `${getBaseUrl()}/wp-admin/admin.php?page=es_campaigns#!/gallery?manageTemplates=yes` },
    { label: "New Form", href: `${getBaseUrl()}/wp-admin/admin.php?page=es_forms&action=new` },
    { label: "New List", href: `${getBaseUrl()}/wp-admin/admin.php?page=es_lists&action=new` },
    { label: "New Contact", href: `${getBaseUrl()}/wp-admin/admin.php?page=es_subscribers&action=new` }
  ];

  const toggleDropdown = () => {
    setIsDropdownOpen(!isDropdownOpen);
  };

  return (
    <div
      className="box-border content-stretch flex items-center justify-between p-0 relative size-full"
      data-node-id="52:18330"
    >
      <div
        className="box-border content-stretch flex flex-col gap-1 items-start justify-start leading-[0] not-italic p-0 relative shrink-0"
        data-node-id="52:18331"
      >
        <div
          className="min-w-full relative shrink-0"
          data-node-id="52:18332"
          style={{ width: "min-content" }}
        >
          <p className="font-['Inter'] font-medium block leading-[20px] text-[14px] text-neutral-500">{currentDate}</p>
        </div>
        <div className="relative shrink-0 w-[500px]" data-node-id="52:18333">
          <p className="font-['Inter'] font-semibold text-2xl text-neutral-950 block leading-[32px]">
            {getTimeBasedGreeting()}, {getCurrentUserName()}
          </p>
        </div>
      </div>
      <div className="relative">
        <button
          onClick={toggleDropdown}
          className="bg-[#5e19cf] box-border content-stretch flex gap-2 h-9 items-center justify-center px-4 py-2 relative rounded-lg shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] shrink-0 hover:bg-[#4a0fb8] transition-colors duration-200"
          data-name="Button"
          data-node-id="52:18334"
        >
          <div
            className="flex flex-col justify-center leading-[0] not-italic relative shrink-0 text-nowrap"
            id="node-I52_18334-37_925"
          >
            <p className="font-['Inter'] font-medium text-[14px] text-neutral-50 block leading-[20px] whitespace-pre">Create a new</p>
          </div>
          <div
            className={`overflow-clip relative shrink-0 size-4 transition-transform duration-200 ${isDropdownOpen ? 'rotate-180' : ''}`}
            data-name="Icon / ChevronDown"
            id="node-I52_18334-267_4072"
          >
            <img alt="" className="block max-w-none size-full" src={chevronDownOnboarding} />
          </div>
        </button>
        
        {isDropdownOpen && (
          <div className="absolute top-full right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
            {createNewOptions.map((option, index) => (
              <a
                key={index}
                href={option.href}
                target="_blank"
                rel="noopener noreferrer"
                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-150"
                onClick={() => setIsDropdownOpen(false)}
              >
                {option.label}
              </a>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
