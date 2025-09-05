import React, { useState, useEffect } from "react";
// import Alert from "./Alert"; // Re-enabled for the merged version
import DashboardHeader from "./DashboardHeader";
import { SimpleAreaChart } from "./ui/simple-area-chart";

import { 
  activeStatusIcon, 
  draftStatusIcon, 
  inactiveStatusIcon,
  scheduledStatusIcon,
  sendingStatusIcon,
  pausedStatusIcon,
  sentStatusIcon,
  tableChevronRight,
  formsChevronRightIcon,
  // onboardingVideoThumbnail, // Uncomment when onboarding section is enabled
  // freeEmailIconOnboarding // Uncomment when onboarding section is enabled
} from "@/assets/images";
import { 
  getDashboardData, 
  type DashboardData, 
  parseIntSafe, 
  parseFloatSafe, 
  formatNumber, 
  formatPercentage,
  getPluginButtonInfo,
  createDashboardWorkflow,
  canCreateAbandonedCartWorkflow,
  getPricingPageUrl,
  getSubscribersStats,
  getOnboardingSteps,
  type OnboardingOptions
} from "../api/dashboard";
import { getBaseUrl } from "../api/client";

const adminData = (window as any).icegramExpressAdminData;

const images = {
  chartIconDashboard: `${adminData.baseUrl}/images/chart-icon-dashboard.svg`,
  trendingUpIcon: `${adminData.baseUrl}/images/trending-up-icon.svg`,
  workflowIcon: `${adminData.baseUrl}/images/workflow-icon.svg`,
  workflowInfoIcon: `${adminData.baseUrl}/images/workflow-info-icon.svg`,
  workflowBuildIcon: `${adminData.baseUrl}/images/workflow-build-icon.svg`,
  workflowWelcomeContactsIcon: `${adminData.baseUrl}/images/workflow-welcome-contacts-icon.svg`,
  workflowSubscriberWelcomeIcon: `${adminData.baseUrl}/images/workflow-subscriber-welcome-icon.svg`,
  workflowTaggedCustomersIcon: `${adminData.baseUrl}/images/workflow-tagged-customers-icon.svg`,
  workflowChevronRightIcon: `${adminData.baseUrl}/images/workflow-chevron-right-icon.svg`,
  helpCircleHelpIcon: `${adminData.baseUrl}/images/help-circle-help-icon.svg`,
  helpAccordionChevronIcon: `${adminData.baseUrl}/images/help-accordion-chevron-icon.svg`,
  helpMessageCircleQuestionIcon: `${adminData.baseUrl}/images/help-message-circle-question-icon.svg`,
  helpMailIcon: `${adminData.baseUrl}/images/help-mail-icon.svg`,
  emailDeliveryMistakes: `${adminData.baseUrl}/images/email-delivery-mistakes.png`,
  monthlyNewsletter: `${adminData.baseUrl}/images/monthly-newsletter.png`,
  reliableWordpressEmailDelivery: `${adminData.baseUrl}/images/reliable-wordpress-email-delivery.png`,
  emailTemplateForNewSubscriber: `${adminData.baseUrl}/images/email-template-for-new-subscriber.png`,
  popularContentViewAllIcon: `${adminData.baseUrl}/images/popular-content-view-all-icon.svg`,
  icegramExpressIcon: `${adminData.baseUrl}/images/icegram-express-icon.svg`,
  icegramEngageIcon: `${adminData.baseUrl}/images/icegram-engage-icon.svg`,
  icegramCollectIcon: `${adminData.baseUrl}/images/icegram-collect-icon.svg`,
  emailCampaignBg: `${adminData.baseUrl}/images/email-campaign-bg.png`,
  postSidebarImage: `${adminData.baseUrl}/images/post-sidebar-image.png`,
  wordpressIcon: `${adminData.baseUrl}/images/wordpress-icon.png`,
  emailConfigSvg: `${adminData.baseUrl}/images/email-config.svg`,
  emailProvider1: `${adminData.baseUrl}/images/email-provider-1.png`,
  emailProvider2: `${adminData.baseUrl}/images/email-provider-2.png`,
  emailProvider3: `${adminData.baseUrl}/images/email-provider-3.png`,
  emailProvider4: `${adminData.baseUrl}/images/email-provider-4.png`,
  freeEmailIconOnboarding: `${adminData.baseUrl}/images/free-email-icon-onboarding.svg`,
  onboardingVideoThumbnail: `${adminData.baseUrl}/images/onboarding-video-thumbnail.png`,
  audience: `${adminData.baseUrl}/images/audience.png`,
};

export default function Dashboard() {
  const [dashboardData, setDashboardData] = useState<DashboardData | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [workflowCreating, setWorkflowCreating] = useState<string | null>(null);
  
  // Email performance filter state
  const [selectedPeriod, setSelectedPeriod] = useState<7 | 30 | 60>(7);
  const [isLoadingStats, setIsLoadingStats] = useState(false);

  // Onboarding checklist state
  const [onboardingSteps, setOnboardingSteps] = useState<OnboardingOptions>({
    sendFirstCampaign: false, // Will be loaded from WordPress options
    importContacts: false,
    createSubscriptionForm: false,
    createWorkflow: false
  });

  // Track which onboarding task is expanded
  const [expandedTask, setExpandedTask] = useState<keyof OnboardingOptions | null>(null);

  const handleWorkflowCreate = async (workflowType: 'welcome-email' | 'confirmation-email') => {
    try {
      setWorkflowCreating(workflowType);
      
      const response = await createDashboardWorkflow(workflowType);
      
      // Check if we have a valid workflow_id
      if (!response.workflow_id) {
        console.error('No workflow ID in response:', response);
        console.error('Response structure:', JSON.stringify(response, null, 2));
        alert(`Workflow creation failed: No workflow ID returned. Check console for details.`);
        return;
      }
      
      if (response.edit_url && response.edit_url.startsWith('http')) {
        alert(`${response.message}\nRedirecting to edit page...`);
        
        setTimeout(() => {
          window.location.href = response.edit_url;
        }, 1000);
      } else {
        console.error('Invalid edit URL received:', response.edit_url);
        console.error('Full response:', JSON.stringify(response, null, 2));
        alert(`Workflow created successfully (ID: ${response.workflow_id}), but redirect URL is invalid: "${response.edit_url}"\n\nCheck console for details.`);
      }
      
    } catch (error) {
      console.error('Workflow creation error:', error);
      const errorMessage = error instanceof Error ? error.message : 'Failed to create workflow';
      alert(`Error: ${errorMessage}\n\nCheck console for details.`);
    } finally {
      setWorkflowCreating(null);
    }
  };

  const handleAbandonedCartClick = async () => {
    const userPlan = dashboardData?.plan || 'lite';
    
    if (canCreateAbandonedCartWorkflow(userPlan)) {
      // User has Pro plan - create workflow and redirect to edit page
      try {
        setWorkflowCreating('abandoned-cart');
        
        const response = await createDashboardWorkflow('abandoned-cart');
        
        // Check if we have a valid workflow_id
        if (!response.workflow_id) {
          console.error('No workflow ID in abandoned cart response:', response);
          alert(`Workflow creation failed: No workflow ID returned. Check console for details.`);
          return;
        }
        
        if (response.edit_url && response.edit_url.startsWith('http')) {
          alert(`${response.message}\nRedirecting to edit page...`);
          
          setTimeout(() => {
            window.location.href = response.edit_url;
          }, 1000);
        } else {
          console.error('Invalid edit URL received for abandoned cart:', response.edit_url);
          console.error('Full response:', JSON.stringify(response, null, 2));
          alert(`Workflow created successfully (ID: ${response.workflow_id}), but redirect URL is invalid: "${response.edit_url}"\n\nCheck console for details.`);
        }
        
      } catch (error) {
        console.error('Abandoned cart workflow creation error:', error);
        const errorMessage = error instanceof Error ? error.message : 'Failed to create abandoned cart workflow';
        alert(`Error: ${errorMessage}\n\nCheck console for details.`);
      } finally {
        setWorkflowCreating(null);
      }
    } else {
      // User doesn't have Pro plan - redirect to pricing page
      const pricingUrl = getPricingPageUrl();
      window.location.href = pricingUrl;
    }
  };

  const handleGetFreeEmailSendingClick = () => {
    setShowIcegramMailerPopup(true);
  };

  const [openFaqItems, setOpenFaqItems] = useState<number[]>([]);
  const [showIcegramMailerPopup, setShowIcegramMailerPopup] = useState(false);
  
  const toggleFaqItem = (index: number) => {
    setOpenFaqItems(prev =>
      prev.includes(index) ? prev.filter(i => i !== index) : [...prev, index]
    );
  };


  // Handle task label click to expand/collapse
  const handleTaskLabelClick = (step: keyof OnboardingOptions) => {
    setExpandedTask(expandedTask === step ? null : step);
  };

  // Calculate onboarding completion
  const getOnboardingCompletion = () => {
    const completedSteps = Object.values(onboardingSteps).filter(Boolean).length;
    const totalSteps = Object.keys(onboardingSteps).length;
    return { completed: completedSteps, total: totalSteps };
  };

  // Handle period filter change and fetch new stats
  const handlePeriodChange = async (days: 7 | 30 | 60) => {
    if (days === selectedPeriod) return;
    
    setSelectedPeriod(days);
    setIsLoadingStats(true);
    
    try {
      const newStats = await getSubscribersStats(days);
      
      setDashboardData(prev => prev ? {
        ...prev,
        dashboard_kpi: {
          ...prev.dashboard_kpi,
          total_subscribed: newStats.total_subscribed,
          total_email_opens: newStats.total_email_opens,
          total_links_clicks: newStats.total_links_clicks,
          total_message_sent: newStats.total_message_sent,
          total_unsubscribed: newStats.total_unsubscribed,
          avg_open_rate: newStats.avg_open_rate,
          avg_click_rate: newStats.avg_click_rate,
          avg_unsubscribe_rate: newStats.avg_unsubscribe_rate,
          contacts_growth: newStats.contacts_growth,
          avg_bounce_rate: newStats.avg_bounce_rate,
          total_hard_bounced_contacts: newStats.total_hard_bounced_contacts,
          hard_bounces_before_two_months: newStats.hard_bounces_before_two_months,
          hard_bounces_percentage_growth: newStats.hard_bounces_percentage_growth,
          sent_percentage_growth: newStats.sent_percentage_growth,
          sent_before_two_months: newStats.sent_before_two_months,
          open_percentage_growth: newStats.open_percentage_growth,
          open_before_two_months: newStats.open_before_two_months,
          click_percentage_growth: newStats.click_percentage_growth,
          click_before_two_months: newStats.click_before_two_months,
          unsubscribe_percentage_growth: newStats.unsubscribe_percentage_growth
        }
      } : null);
    } catch (error) {
      console.error('Failed to fetch stats:', error);
      // Could add error handling UI here
    } finally {
      setIsLoadingStats(false);
    }
  };

  useEffect(() => {
    const loadDashboardDataAndStats = async () => {
      try {
        setIsLoading(true);
        const [data, stats, onboardingData] = await Promise.all([
          getDashboardData(),
          getSubscribersStats(selectedPeriod),
          getOnboardingSteps()
        ]);
        setDashboardData({
          ...data,
          dashboard_kpi: {
            ...data.dashboard_kpi,
            ...stats
          }
        });
        console.log('🔍 Loaded onboarding data from API:', onboardingData);
        setOnboardingSteps(onboardingData);
      } catch (error) {
        setDashboardData({
          campaigns: [],
          audience_activity: [],
          forms: [],
          lists: [],
          dashboard_kpi: {
            campaigns: [],
            total_subscribed: "0",
            total_email_opens: "0",
            total_links_clicks: "0",
            total_message_sent: "0",
            total_unsubscribed: "0",
            avg_open_rate: "0",
            avg_click_rate: "0",
            avg_unsubscribe_rate: "0",
            contacts_growth: {},
            avg_bounce_rate: 0,
            total_hard_bounced_contacts: "0",
            hard_bounces_before_two_months: "0",
            hard_bounces_percentage_growth: 0,
            sent_percentage_growth: 0,
            sent_before_two_months: "0",
            open_percentage_growth: 0,
            open_before_two_months: "0",
            click_percentage_growth: 0,
            click_before_two_months: "0",
            unsubscribe_percentage_growth: 0,
          }
        });
      } finally {
        setIsLoading(false);
      }
    };

    loadDashboardDataAndStats();
  }, []);
  

  if (!dashboardData) {
    return null;
  }

  const rawData = dashboardData.dashboard_kpi.contacts_growth;

  const chartData = Object.entries(rawData).map(([key, value]) => {
    const [year, month] = key.split("-");
    const date = new Date(Number(year), Number(month) - 1); // month index is 0-based
    const monthName = date.toLocaleString("default", { month: "short" }); // "Jan", "Feb" etc.
    return {
      month: `${monthName} ${year}`, // e.g. "Sep 2024"
      desktop: value,
    };
  }).slice(-6);

  return (
    <div className="min-h-screen" style={{ backgroundColor: "#F6F5F8" }}>
      
      <div className="pl-6 pr-6 py-7 flex flex-col gap-7">
        <div className="w-full">
          <DashboardHeader />
        </div>

        {/* Onboarding Section - Hidden for now */}
        <div className="flex flex-col gap-3 w-full" data-node-id="49:10651">
          <div className="flex items-center justify-between w-full" data-node-id="49:10652">
            <div 
              className="font-['Inter'] font-normal text-[16px] text-neutral-950 leading-[24px]"
              data-node-id="49:10653"
            >
              Finish your onboarding and receive 200 free emails/month
            </div>
            <div 
              className="flex flex-col gap-2.5 rounded-[9999px] w-[185px] shrink-0 p-3"
              data-node-id="52:18151"
            >
              <div className="font-['Inter'] font-normal text-[14px] text-neutral-500">
                {getOnboardingCompletion().completed}/{getOnboardingCompletion().total} steps completed
              </div>
              <div className="flex flex-col h-1.5 py-0 relative">
                <div className="bg-gray-200 h-1.5 rounded-[9999px] w-full">
                  <div 
                    className="bg-[#5e19cf] h-1.5 rounded-[9999px] transition-all duration-300"
                    style={{ 
                      width: `${(getOnboardingCompletion().completed / getOnboardingCompletion().total) * 100}%` 
                    }}
                  />
                </div>
              </div>
            </div>
          </div>
          
          <div className="flex gap-4 w-full items-stretch" data-node-id="52:18463">
            <div className="flex flex-col gap-2 w-2/3 h-full" data-node-id="52:18464">
              
              {/* Task 1 - Send First Campaign */}
              <div 
                className={`bg-[#ffffff] flex gap-[19px] items-center px-3 py-4 rounded-lg border border-[rgba(163,163,163,0.01)] w-full hover:bg-gray-50 transition-all duration-300 ease-in-out overflow-hidden ${
                  expandedTask === 'sendFirstCampaign' ? 'h-auto' : 'h-[56px]'
                }`}
                data-node-id="52:18465"
              >
                <div className={`${expandedTask === 'sendFirstCampaign' ? 'basis-0 grow min-h-px min-w-px' : 'flex-1'} box-border content-stretch flex gap-2 items-start justify-start overflow-visible p-0 relative shrink-0`}>
                  <div className="flex items-center pointer-events-none">
                    <div className={`rounded size-4 border shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] flex items-center justify-center ${
                      onboardingSteps.sendFirstCampaign 
                        ? 'bg-[#5e19cf] border-[#5e19cf]' 
                        : 'bg-[#ffffff] border-neutral-400'
                    }`}>
                      {onboardingSteps.sendFirstCampaign && (
                        <svg className="w-3.5 h-3.5" viewBox="0 0 14 14" fill="none">
                          <path d="M11.6667 3.5L5.25 9.91667L2.33333 7" stroke="rgba(250, 250, 250, 1)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                      )}
                    </div>
                  </div>
                  <div className="content-stretch flex flex-col gap-1.5 items-start justify-start leading-[0] not-italic relative shrink-0 text-[14px]">
                    <a
                      onClick={() => handleTaskLabelClick('sendFirstCampaign')}
                      className="text-left cursor-pointer hover:text-[#5e19cf] transition-colors focus:outline-none"
                    >
                      <div className={`font-['Inter'] font-medium text-[14px] text-neutral-950 leading-[20px] ${
                        onboardingSteps.sendFirstCampaign ? 'line-through opacity-70' : ''
                      }`}>
                        Send your first campaign
                      </div>
                    </a>
                    {expandedTask === 'sendFirstCampaign' && (
                      <div className="font-['Inter'] font-normal text-[14px] text-neutral-500 leading-[20px]">
                        Create and send your first email campaign to connect with your audience.
                      </div>
                    )}
                  </div>
                </div>
                
                {expandedTask === 'sendFirstCampaign' && (
                  <div className="box-border content-stretch flex gap-2 h-9 items-center justify-center relative rounded-[8px] shrink-0">
                    <a 
                      href={`${getBaseUrl()}/wp-admin/admin.php?page=es_campaigns#!/gallery?campaignType=newsletter`}
                      className="box-border content-stretch flex gap-2 items-center justify-center px-4 py-2 relative rounded-[8px] size-full border border-[#5e19cf] shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] hover:bg-[#5e19cf] transition-colors duration-200 cursor-pointer text-decoration-none group" target="_blank"
                    >
                      <div className="flex flex-col font-['Inter'] font-medium justify-center leading-[0] not-italic relative shrink-0 text-[#5e19cf] text-[14px] text-nowrap group-hover:text-white transition-colors duration-200">
                        <p className="leading-[20px] whitespace-pre">Create</p>
                      </div>
                    </a>
                  </div>
                )}
              </div>
              
              {/* Task 2 - Import Contacts */}
              <div 
                className={`bg-[#ffffff] flex gap-[19px] items-center px-3 py-4 rounded-lg border border-[rgba(163,163,163,0.01)] w-full hover:bg-gray-50 transition-all duration-300 ease-in-out overflow-hidden ${
                  expandedTask === 'importContacts' ? 'h-auto' : 'h-[56px]'
                }`}
                data-node-id="52:18467"
              >
                <div className={`${expandedTask === 'importContacts' ? 'basis-0 grow min-h-px min-w-px' : 'flex-1'} box-border content-stretch flex gap-2 items-start justify-start overflow-visible p-0 relative shrink-0`}>
                  <div className="flex items-center pointer-events-none">
                    <div className={`rounded size-4 border shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] flex items-center justify-center ${
                      onboardingSteps.importContacts 
                        ? 'bg-[#5e19cf] border-[#5e19cf]' 
                        : 'bg-[#ffffff] border-neutral-400'
                    }`}>
                      {onboardingSteps.importContacts && (
                        <svg className="w-3.5 h-3.5" viewBox="0 0 14 14" fill="none">
                          <path d="M11.6667 3.5L5.25 9.91667L2.33333 7" stroke="rgba(250, 250, 250, 1)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                      )}
                    </div>
                  </div>
                  <div className="content-stretch flex flex-col gap-1.5 items-start justify-start leading-[0] not-italic relative shrink-0 text-[14px]">
                    <a
                      onClick={() => handleTaskLabelClick('importContacts')}
                      className="text-left cursor-pointer hover:text-[#5e19cf] transition-colors focus:outline-none"
                    >
                      <div className={`font-['Inter'] font-medium text-[14px] text-neutral-950 leading-[20px] ${
                        onboardingSteps.importContacts ? 'line-through opacity-70' : ''
                      }`}>
                        Import contacts
                      </div>
                    </a>
                    {expandedTask === 'importContacts' && (
                      <div className="font-['Inter'] font-normal text-[14px] text-neutral-500 leading-[20px]">
                        Easily upload your existing contacts to start engaging with your audience right away.
                      </div>
                    )}
                  </div>
                </div>
                
                {expandedTask === 'importContacts' && (
                  <div className="box-border content-stretch flex gap-2 h-9 items-center justify-center relative rounded-[8px] shrink-0">
                    <a 
                      href={`${getBaseUrl()}/wp-admin/admin.php?page=es_subscribers&action=import`}
                      className="box-border content-stretch flex gap-2 items-center justify-center px-4 py-2 relative rounded-[8px] size-full border border-[#5e19cf] shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] hover:bg-[#5e19cf] transition-colors duration-200 cursor-pointer text-decoration-none group" target="_blank"
                    >
                      <div className="flex flex-col font-['Inter'] font-medium justify-center leading-[0] not-italic relative shrink-0 text-[#5e19cf] text-[14px] text-nowrap group-hover:text-white transition-colors duration-200">
                        <p className="leading-[20px] whitespace-pre">Import</p>
                      </div>
                    </a>
                  </div>
                )}
              </div>
              
              {/* Task 3 - Create Subscription Form */}
              <div 
                className={`bg-[#ffffff] flex gap-[19px] items-center px-3 py-4 rounded-lg border border-[rgba(163,163,163,0.01)] w-full hover:bg-gray-50 transition-all duration-300 ease-in-out overflow-hidden ${
                  expandedTask === 'createSubscriptionForm' ? 'h-auto' : 'h-[56px]'
                }`}
                data-node-id="52:18470"
              >
                <div className={`${expandedTask === 'createSubscriptionForm' ? 'basis-0 grow min-h-px min-w-px' : 'flex-1'} box-border content-stretch flex gap-2 items-start justify-start overflow-visible p-0 relative shrink-0`}>
                  <div className="flex items-center pointer-events-none">
                    <div className={`rounded size-4 border shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] flex items-center justify-center ${
                      onboardingSteps.createSubscriptionForm 
                        ? 'bg-[#5e19cf] border-[#5e19cf]' 
                        : 'bg-[#ffffff] border-neutral-400'
                    }`}>
                      {onboardingSteps.createSubscriptionForm && (
                        <svg className="w-3.5 h-3.5" viewBox="0 0 14 14" fill="none">
                          <path d="M11.6667 3.5L5.25 9.91667L2.33333 7" stroke="rgba(250, 250, 250, 1)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                      )}
                    </div>
                  </div>
                  <div className="content-stretch flex flex-col gap-1.5 items-start justify-start leading-[0] not-italic relative shrink-0 text-[14px]">
                    <a
                      onClick={() => handleTaskLabelClick('createSubscriptionForm')}
                      className="text-left cursor-pointer hover:text-[#5e19cf] transition-colors focus:outline-none"
                    >
                      <div className={`font-['Inter'] font-medium text-[14px] text-neutral-950 leading-[20px] ${
                        onboardingSteps.createSubscriptionForm ? 'line-through opacity-70' : ''
                      }`}>
                        Create a subscription form
                      </div>
                    </a>
                    {expandedTask === 'createSubscriptionForm' && (
                      <div className="font-['Inter'] font-normal text-[14px] text-neutral-500 leading-[20px]">
                        Set up a subscription form to start building your email list.
                      </div>
                    )}
                  </div>
                </div>
                
                {expandedTask === 'createSubscriptionForm' && (
                  <div className="box-border content-stretch flex gap-2 h-9 items-center justify-center relative rounded-[8px] shrink-0">
                    <a 
                      href={`${getBaseUrl()}/wp-admin/admin.php?page=es_forms&action=new`}
                      className="box-border content-stretch flex gap-2 items-center justify-center px-4 py-2 relative rounded-[8px] size-full border border-[#5e19cf] shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] hover:bg-[#5e19cf] transition-colors duration-200 cursor-pointer text-decoration-none group" target="_blank"
                    >
                      <div className="flex flex-col font-['Inter'] font-medium justify-center leading-[0] not-italic relative shrink-0 text-[#5e19cf] text-[14px] text-nowrap group-hover:text-white transition-colors duration-200">
                        <p className="leading-[20px] whitespace-pre">Create</p>
                      </div>
                    </a>
                  </div>
                )}
              </div>              
              {/* Task 4 - Create Workflow */}
              <div 
                className={`bg-[#ffffff] flex gap-[19px] items-center px-3 py-4 rounded-lg border border-[rgba(163,163,163,0.01)] w-full hover:bg-gray-50 transition-all duration-300 ease-in-out overflow-hidden ${
                  expandedTask === 'createWorkflow' ? 'h-auto' : 'h-[56px]'
                }`}
                data-node-id="52:18472"
              >
                <div className={`${expandedTask === 'createWorkflow' ? 'basis-0 grow min-h-px min-w-px' : 'flex-1'} box-border content-stretch flex gap-2 items-start justify-start overflow-visible p-0 relative shrink-0`}>
                  <div className="flex items-center pointer-events-none">
                    <div className={`rounded size-4 border shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] flex items-center justify-center ${
                      onboardingSteps.createWorkflow 
                        ? 'bg-[#5e19cf] border-[#5e19cf]' 
                        : 'bg-[#ffffff] border-neutral-400'
                    }`}>
                      {onboardingSteps.createWorkflow && (
                        <svg className="w-3.5 h-3.5" viewBox="0 0 14 14" fill="none">
                          <path d="M11.6667 3.5L5.25 9.91667L2.33333 7" stroke="rgba(250, 250, 250, 1)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                      )}
                    </div>
                  </div>
                  <div className="content-stretch flex flex-col gap-1.5 items-start justify-start leading-[0] not-italic relative shrink-0 text-[14px]">
                    <a
                      onClick={() => handleTaskLabelClick('createWorkflow')}
                      className="text-left cursor-pointer hover:text-[#5e19cf] transition-colors focus:outline-none"
                    >
                      <div className={`font-['Inter'] font-medium text-[14px] text-neutral-950 leading-[20px] ${
                        onboardingSteps.createWorkflow ? 'line-through opacity-70' : ''
                      }`}>
                        Create a workflow
                      </div>
                    </a>
                    {expandedTask === 'createWorkflow' && (
                      <div className="font-['Inter'] font-normal text-[14px] text-neutral-500 leading-[20px]">
                        Set up automated workflows to streamline your email marketing campaigns.
                      </div>
                    )}
                  </div>
                </div>
                
                {expandedTask === 'createWorkflow' && (
                  <div className="box-border content-stretch flex gap-2 h-9 items-center justify-center relative rounded-[8px] shrink-0">
                    <a 
                      href={`${getBaseUrl()}/wp-admin/admin.php?page=es_workflows&action=new`}
                      className="box-border content-stretch flex gap-2 items-center justify-center px-4 py-2 relative rounded-[8px] size-full border border-[#5e19cf] shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] hover:bg-[#5e19cf] transition-colors duration-200 cursor-pointer text-decoration-none group" target="_blank"
                    >
                      <div className="flex flex-col font-['Inter'] font-medium justify-center leading-[0] not-italic relative shrink-0 text-[#5e19cf] text-[14px] text-nowrap group-hover:text-white transition-colors duration-200">
                        <p className="leading-[20px] whitespace-pre">Create</p>
                      </div>
                    </a>
                  </div>
                )}
              </div>
              
              {/* Bonus Item */}
              <button 
                onClick={handleGetFreeEmailSendingClick}
                className="bg-[#ffffff] flex gap-[19px] items-center px-3 py-5 rounded-lg border border-[rgba(163,163,163,0.01)] w-full hover:bg-gray-50 transition-colors cursor-pointer"
                data-node-id="52:18474"
              >
                <div className="flex gap-2 items-center">
                  <div className="size-5">
                    <img alt="" className="w-5 h-5" src={images.freeEmailIconOnboarding} />
                  </div>
                  <div className="font-['Inter'] font-medium text-[#5e19cf] text-[14px] leading-[20px]">
                    Get Free Email-Sending
                  </div>
                </div>
              </button>
            </div>
            
            <div className="w-1/3" data-node-id="52:18479">
              <div className="block">
                <div className="content-stretch flex flex-col gap-3 items-start justify-start relative size-full">
                  <div className="bg-white box-border content-stretch flex flex-col gap-3 items-start justify-start overflow-clip p-[12px] relative rounded-[14px] shrink-0 w-full" data-name="Card" data-node-id="52:18739">
                    <div className="content-stretch flex flex-col gap-2 items-start justify-start relative shrink-0 w-full" data-name="Card Content">
                      <div className="aspect-[240/135] rounded-[4px] shrink-0 w-full overflow-hidden" data-name="Ratio=16:9">
                        <iframe 
                          width="100%" 
                          height="100%" 
                          src="https://www.youtube.com/embed/TPL5HxdB1N0?rel=0&modestbranding=1" 
                          title="Icegram Express Overview"
                          frameBorder="0" 
                          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                          allowFullScreen
                          className="w-full h-full rounded-[4px]"
                        />
                      </div>
                    </div>
                    <div className="content-stretch flex gap-2 items-start justify-start relative shrink-0 w-full" data-name="Card Header">
                      <div className="basis-0 content-stretch flex flex-col gap-1.5 grow items-start justify-start leading-[0] min-h-px min-w-px not-italic relative shrink-0" data-name="Text">
                        <div className="relative shrink-0  w-full">
                          <p className="font-['Inter'] font-semibold leading-[28px] text-[18px] text-neutral-950">Watch 3 min overview</p>
                        </div>
                        <div className="relative shrink-0  w-full">
                          <p className="font-['Inter'] font-medium leading-[20px] text-[14px] text-neutral-500">Checkout demo of Icegram by our team</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div
          className="box-border content-stretch flex flex-col gap-3 items-start justify-start p-0 relative w-full"
          data-node-id="49:10703"
        >
          <div
            className="leading-[0] not-italic relative shrink-0 w-full"
            data-node-id="49:10704"
          >
            <p className="font-['Inter'] font-semibold text-[18px] text-neutral-950 block leading-[28px]">Quick start</p>
          </div>
          <div
            className="box-border content-stretch flex gap-4 items-center justify-start p-0 relative shrink-0 w-full"
            data-node-id="60:22074"
          >
            <div
              className="basis-0 bg-[#ffffff] box-border content-stretch flex flex-col gap-4 grow items-start justify-start min-h-px min-w-px p-[16px] relative rounded-xl shrink-0"
              data-name="Create New email campaign"
              data-node-id="60:22075"
            >
              <div
                aria-hidden="true"
                className="absolute border border-[rgba(163,163,163,0.01)] border-solid inset-0 pointer-events-none rounded-xl"
              />
              <div
                className="leading-[0] min-w-full not-italic relative shrink-0"
                data-node-id="60:22076"
                style={{ width: "min-content" }}
              >
                <p className="font-['Inter'] font-normal text-[16px] text-neutral-950 block leading-[24px]">Create a new Email Campaign</p>
              </div>
              <div
                className="box-border content-stretch flex gap-4 items-center justify-start p-0 relative shrink-0 w-full"
                data-node-id="60:22077"
              >
                <a
                  href={`${getBaseUrl()}/wp-admin/admin.php?page=es_campaigns#!/gallery?campaignType=newsletter`}
                  
                  rel="noopener noreferrer"
                  className="basis-0 bg-[#ffffff] grow min-h-px min-w-px relative rounded-[14px] shrink-0 hover:shadow-lg transition-shadow duration-200 cursor-pointer block"
                  data-name="Card"
                  data-node-id="60:22698"
                >
                  <div className="box-border content-stretch flex flex-col gap-3 justify-start overflow-clip pb-3 pt-0 px-0 relative w-full">
                    <div className="relative">
                      <img
                        src={`${adminData.baseUrl}/images/Email-Campaign- Newsletter-Broadcast.png`}
                        alt="Post Notification"
                        className="w-full h-[180px] object-cover rounded-t-[14px]"
                      />

                      {/* Draft badge overlay */}
                      <div className="absolute bg-[#efe8fa] box-border content-stretch flex gap-2 items-center justify-center px-2 py-1.5 right-[10.33px] rounded-lg top-[9.98px] w-16"><div aria-hidden="true" className="absolute border border-[rgba(205,184,240,0.5)] border-solid inset-0 pointer-events-none rounded-lg"></div><div className="leading-[0] not-italic relative shrink-0 text-nowrap"><p className="font-['Inter'] font-medium text-[#5e19cf] text-[12px] block leading-[20px] whitespace-pre">Draft</p></div></div>
                    </div>

                    <div className="box-border content-stretch flex gap-2 items-start justify-start px-3 py-0 relative shrink-0 w-full" data-name="Card Header">
                      <div className="basis-0 box-border content-stretch flex flex-col gap-1.5 grow justify-start leading-[0] min-h-px min-w-px not-italic p-0 relative shrink-0" data-name="Text">
                        <div className="relative shrink-0 w-full">
                          <p className="font-['Inter'] font-semibold text-[16px] text-neutral-950 block leading-[24px]">Email Campaign - Newsletter Broadcast</p>
                        </div>
                        <div className="relative shrink-0 w-full">
                          <p className="font-['Inter'] font-normal text-[14px] text-neutral-500 block leading-[20px]">Complete creating your first newsletter broadcast</p>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div aria-hidden="true" className="absolute border border-neutral-200 border-solid inset-0 pointer-events-none rounded-[14px]" />
                </a>

                <a
                  href={`${getBaseUrl()}/wp-admin/admin.php?page=es_campaigns#!/gallery?campaignType=post_notification`}
                  
                  rel="noopener noreferrer"
                  className="basis-0 bg-[#ffffff] grow min-h-px min-w-px relative rounded-[14px] shrink-0 hover:shadow-lg transition-shadow duration-200 cursor-pointer block"
                  data-name="Card"
                  data-node-id="60:22079"
                >
                  <div className="box-border content-stretch flex flex-col gap-3 justify-start overflow-clip pb-3 pt-0 px-0 relative w-full">
                    <div className="relative">
                      <img
                        src={`${adminData.baseUrl}/images/Email-Campaign- Post-Notification.png`}
                        alt="Post Notification"
                        className="w-full h-[180px] object-cover rounded-t-[14px]"
                      />

                      {/* Draft badge overlay */}
                      <div className="absolute bg-[#efe8fa] box-border content-stretch flex gap-2 items-center justify-center px-2 py-1.5 right-[10.33px] rounded-lg top-[9.98px] w-16"><div aria-hidden="true" className="absolute border border-[rgba(205,184,240,0.5)] border-solid inset-0 pointer-events-none rounded-lg"></div><div className="leading-[0] not-italic relative shrink-0 text-nowrap"><p className="font-['Inter'] font-medium text-[#5e19cf] text-[12px] block leading-[20px] whitespace-pre">Draft</p></div></div>
                    </div>

                    <div className="box-border content-stretch flex gap-2 items-start justify-start px-3 py-0 relative shrink-0 w-full" data-name="Card Header">
                      <div className="basis-0 box-border content-stretch flex flex-col gap-1.5 grow items-start justify-start leading-[0] min-h-px min-w-px not-italic p-0 relative shrink-0" data-name="Text">
                        <div className="relative shrink-0 w-full">
                          <p className="font-['Inter'] font-semibold text-[16px] text-neutral-950 block leading-[24px]">Email Campaign - Post <br /> Notification</p>
                        </div>
                        <div className="relative shrink-0 w-full">
                          <p className="font-['Inter'] font-normal text-[14px] text-neutral-500 block leading-[20px] whitespace-pre-wrap">{`Complete creating your first  new post notification`}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div aria-hidden="true" className="absolute border border-neutral-200 border-solid inset-0 pointer-events-none rounded-[14px]" />
                </a>

                <a
                  href={`${getBaseUrl()}/wp-admin/admin.php?page=es_settings&section=ess#tabs-email_sending`}
                  
                  rel="noopener noreferrer"
                  className="basis-0 bg-[#ffffff] grow min-h-px min-w-px relative rounded-[14px] shrink-0 hover:shadow-lg transition-shadow duration-200 cursor-pointer block"
                  data-name="Card"
                  data-node-id="60:22728"
                >
                  <div className="box-border content-stretch flex flex-col gap-3 justify-start overflow-clip pb-3 pt-0 px-0 relative w-full">
                    <div className="relative">
                      <img
                        src={`${adminData.baseUrl}/images/Configure-Email-Sending.png`}
                        alt="Post Notification"
                        className="w-full h-[180px] object-cover rounded-t-[14px]"
                      />
                    </div>
                    <div className="box-border content-stretch flex gap-2 items-start justify-start px-3 py-0 relative shrink-0 w-full" data-name="Card Header">
                      <div className="basis-0 box-border content-stretch flex flex-col gap-1.5 grow items-start justify-start leading-[0] min-h-px min-w-px not-italic p-0 relative shrink-0" data-name="Text">
                        <div className="relative shrink-0 w-full">
                          <p className="font-['Inter'] font-semibold text-[16px] text-neutral-950 block leading-[24px]">
                            Configure Email <br /> Sending
                          </p>
                        </div>
                        <div className="relative shrink-0 w-full">
                          <p className="font-['Inter'] font-normal text-[14px] text-neutral-500 block leading-[20px]">
                            Essential for high email delivery and reaching the inbox, and much more
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div aria-hidden="true" className="absolute border border-neutral-200 border-solid inset-0 pointer-events-none rounded-[14px]" />
                </a>
              </div>
            </div>
            
            <div className="flex flex-row items-center self-stretch">
              <div
                className="bg-[#efe8fa] h-full relative rounded-[14px] shrink-0 w-[299px]"
                data-name="Card"
                data-node-id="60:22081"
              >
                <div className="box-border content-stretch flex flex-col gap-3 h-full items-start justify-start overflow-clip px-0 py-4 relative w-[299px]">
                  <div className="box-border content-stretch flex gap-2 items-start justify-start px-4 py-0 relative shrink-0 w-full" data-name="Card Header">
                    <div className="basis-0 box-border content-stretch flex flex-col gap-1.5 grow items-start justify-start min-h-px min-w-px p-0 relative shrink-0" data-name="Text">
                      <div className="leading-[0] not-italic relative shrink-0 w-full">
                        <p className="font-['Inter'] font-normal text-[14px] text-neutral-900 block leading-[20px]">About 2 mins</p>
                      </div>
                    </div>
                  </div>
                  <div className="basis-0 box-border content-stretch flex gap-2 grow items-start justify-start min-h-px min-w-px px-4 py-0 relative shrink-0 w-full" data-name="Card Header">
                    <div className="basis-0 box-border content-stretch flex flex-col gap-1.5 grow items-start justify-start leading-[0] min-h-px min-w-px not-italic p-0 relative shrink-0" data-name="Text">
                      <div className="relative shrink-0 w-full">
                        <p className="font-['Inter'] font-semibold text-[16px] text-neutral-950 block leading-[24px]">Create a sign up form</p>
                      </div>
                      <div className="relative shrink-0 w-full">
                        <p className="font-['Inter'] font-normal text-[14px] text-neutral-500 block leading-[20px]">
                          Collect user details, capture leads, and grow your audience effortlessly
                        </p>
                      </div>
                    </div>
                  </div>
                  <div className="box-border content-stretch flex gap-2 items-start justify-start px-4 py-0 relative shrink-0 w-full" data-name="Card Header">
                    <a 
                      href={`${getBaseUrl()}/wp-admin/admin.php?page=es_forms&action=new`}
                      className="bg-[#5e19cf] box-border content-stretch flex gap-2 h-9 items-center justify-center px-4 py-2 relative rounded-lg shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] shrink-0 hover:bg-[#4a0fb8] transition-colors duration-200 cursor-pointer text-decoration-none"
                      data-name="Button"
                    >
                      <div className="flex flex-col justify-center leading-[0] not-italic relative shrink-0 text-nowrap">
                        <p className="font-['Inter'] font-medium text-[14px] text-neutral-50 block leading-[20px] whitespace-pre">Create a Form</p>
                      </div>
                    </a>
                  </div>
                </div>
                <div aria-hidden="true" className="absolute border border-[#cdb8f0] border-solid inset-0 pointer-events-none rounded-[14px] shadow-sm" />
              </div>
            </div>
          </div>
        </div>

        <div
          className="box-border content-stretch flex flex-col gap-3 items-start justify-start p-0 relative w-full"
          data-node-id="58:7210"
        >
          <div
            className="leading-[0] not-italic relative shrink-0 w-full"
            data-node-id="58:7211"
          >
            <p className="font-['Inter'] font-semibold text-[18px] text-neutral-950 block leading-[28px]">Email performance</p>
          </div>
          <div
            className="bg-[#ffffff] relative rounded-xl shrink-0 w-full"
            data-name="navigation menu content item"
            data-node-id="400:10326"
          >
            <div className="box-border content-stretch flex flex-col gap-6 items-end justify-start overflow-clip p-[16px] relative w-full">
              <div
                className="box-border content-stretch flex items-center justify-between p-0 relative shrink-0 w-full"
                data-node-id="400:10327"
              >
                <div
                  className="leading-[0] not-italic relative shrink-0 text-nowrap"
                  data-node-id="400:10328"
                >
                  <p className="font-['Inter'] font-normal text-[14px] text-neutral-500 block leading-[20px] whitespace-pre">
                    {isLoadingStats ? 'Loading stats...' : 'All your data will show up here'}
                  </p>
                </div>
                <div
                  className="bg-neutral-100 box-border content-stretch flex items-center justify-start p-[8px] relative rounded-[10px] shrink-0 w-[400px]"
                  data-name="Tabs"
                  data-node-id="400:10534"
                >
                  <button
                    onClick={() => handlePeriodChange(7)}
                    disabled={isLoadingStats}
                    className={`basis-0 box-border content-stretch cursor-pointer flex gap-1.5 grow items-center justify-center min-h-px min-w-px overflow-visible px-3 py-1.5 relative rounded-md shrink-0 transition-colors duration-200 ${
                      selectedPeriod === 7 
                        ? 'bg-[#ffffff] shadow-sm' 
                        : 'bg-transparent hover:bg-white/50'
                    } ${isLoadingStats ? 'opacity-50 cursor-not-allowed' : ''}`}
                    data-name="Tabs / Trigger"
                    id="node-I400_10534-183_533"
                  >
                    <div
                      className={`leading-[0] not-italic relative shrink-0 text-center text-nowrap ${
                      selectedPeriod === 7 ? 'text-neutral-950' : 'text-neutral-600'
                      }`}
                      id="node-I400_10534-183_533-183_526"
                    >
                      <p className="font-['Inter'] font-medium text-[14px] block leading-[20px] whitespace-pre">1 week</p>
                    </div>
                  </button>
                  <div className="basis-0 flex flex-row grow items-center self-stretch shrink-0">
                    <button
                      onClick={() => handlePeriodChange(30)}
                      disabled={isLoadingStats}
                      className={`basis-0 box-border content-stretch cursor-pointer flex gap-2 grow h-full items-center justify-center min-h-px min-w-px overflow-visible px-2 py-1 relative rounded-lg shrink-0 transition-colors duration-200 ${
                        selectedPeriod === 30 
                          ? 'bg-[#ffffff] shadow-sm' 
                          : 'bg-transparent hover:bg-white/50'
                      } ${isLoadingStats ? 'opacity-50 cursor-not-allowed' : ''}`}
                      data-name="Tabs / Trigger"
                      id="node-I400_10534-183_535"
                    >
                      <div
                        className={`leading-[0] not-italic relative shrink-0 text-center text-nowrap ${
                          selectedPeriod === 30 ? 'text-neutral-950' : 'text-neutral-600'
                        }`}
                        id="node-I400_10534-183_535-183_529"
                      >
                        <p className="font-['Inter'] font-medium text-[14px] block leading-[20px] whitespace-pre">30 days</p>
                      </div>
                    </button>
                  </div>
                  <div className="basis-0 flex flex-row grow items-center self-stretch shrink-0">
                    <button
                      onClick={() => handlePeriodChange(60)}
                      disabled={isLoadingStats}
                      className={`basis-0 box-border content-stretch cursor-pointer flex gap-2 grow h-full items-center justify-center min-h-px min-w-px overflow-visible px-2 py-1 relative rounded-lg shrink-0 transition-colors duration-200 ${
                        selectedPeriod === 60 
                          ? 'bg-[#ffffff] shadow-sm' 
                          : 'bg-transparent hover:bg-white/50'
                      } ${isLoadingStats ? 'opacity-50 cursor-not-allowed' : ''}`}
                      data-name="Tabs / Trigger"
                      id="node-I400_10534-183_601"
                    >
                      <div
                        className={`leading-[0] not-italic relative shrink-0 text-center text-nowrap ${
                          selectedPeriod === 60 ? 'text-neutral-950' : 'text-neutral-600'
                        }`}
                        id="node-I400_10534-183_601-183_529"
                      >
                        <p className="font-['Inter'] font-medium text-[14px] block leading-[20px] whitespace-pre">60 days</p>
                      </div>
                    </button>
                  </div>
                </div>
              </div>
              <div
                className={`box-border content-stretch flex gap-6 items-center justify-start p-0 relative shrink-0 w-full transition-opacity duration-200 ${
                  isLoadingStats ? 'opacity-50' : 'opacity-100'
                }`}
                data-node-id="400:10330"
              >
                {isLoadingStats && (
                  <div className="absolute inset-0 bg-white/80 flex items-center justify-center z-10 rounded-lg">
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                      <div className="animate-spin rounded-full h-4 w-4 border-2 border-gray-300 border-t-blue-600"></div>
                      Updating stats...
                    </div>
                  </div>
                )}
                <div
                  className="basis-0 box-border content-stretch flex flex-col gap-3 grow items-start justify-center min-h-px min-w-px p-0 relative shrink-0"
                  data-name="Component 2"
                  data-node-id="400:10855"
                >
                  <div
                    className="leading-[0] not-italic relative shrink-0 text-nowrap"
                    data-node-id="400:10856"
                  >
                    <p className="font-['Inter'] font-medium text-[14px] text-gray-500 block leading-[20px] whitespace-pre">Emails Sent</p>
                  </div>
                  <div
                    className="box-border content-stretch flex items-end justify-between p-0 relative shrink-0 w-full"
                    data-node-id="400:10857"
                  >
                    <div
                      className="leading-[0] not-italic relative shrink-0 text-nowrap"
                      data-node-id="400:10858"
                    >
                      <p className="font-['Inter'] font-bold text-[24px] text-neutral-950 block leading-none whitespace-pre">{formatNumber(parseIntSafe(dashboardData.dashboard_kpi.total_message_sent))}</p>
                    </div>
                    <div
                      className="bg-green-100 box-border content-stretch flex gap-1 items-center justify-center px-2 py-1.5 relative rounded-lg shrink-0"
                      data-name="Badge"
                      data-node-id="400:10895"
                    >
                      <div
                        aria-hidden="true"
                        className="absolute border border-[rgba(255,255,255,0)] border-solid inset-0 pointer-events-none rounded-lg"
                      />
                      <div
                        className="overflow-clip relative shrink-0 size-3"
                        data-name="Icon / ArrowUp"
                        id="node-I400_10895-17096_180398"
                      >
                        <img alt="" className="block max-w-none size-full" src={images.trendingUpIcon} />
                      </div>
                      <div
                        className="leading-[0] not-italic relative shrink-0 text-nowrap"
                        id="node-I400_10895-26_171"
                      >
                        <p className="font-['Inter'] font-semibold text-[12px] text-neutral-900 block leading-[16px] whitespace-pre">{formatPercentage(parseFloatSafe(dashboardData.dashboard_kpi.sent_percentage_growth))}</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="flex flex-row items-center self-stretch">
                  <div className="h-full w-px bg-gray-300"></div>
                </div>
                <div
                  className="basis-0 box-border content-stretch flex flex-col gap-3 grow items-start justify-center min-h-px min-w-px p-0 relative shrink-0"
                  data-name="Component 2"
                  data-node-id="400:10877"
                >
                  <div
                    className="leading-[0] not-italic relative shrink-0 text-nowrap"
                    data-node-id="400:10878"
                  >
                    <p className="font-['Inter'] font-medium text-[14px] text-gray-500 block leading-[20px] whitespace-pre">Unsubscribed</p>
                  </div>
                  <div
                    className="box-border content-stretch flex items-end justify-between p-0 relative shrink-0 w-full"
                    data-node-id="400:10879"
                  >
                    <div
                      className="leading-[0] not-italic relative shrink-0 text-nowrap"
                      data-node-id="400:10880"
                    >
                      <p className="font-['Inter'] font-bold text-[24px] text-neutral-950 block leading-none whitespace-pre">{formatNumber(parseIntSafe(dashboardData.dashboard_kpi.total_unsubscribed))}</p>
                    </div>
                    <div
                      className="bg-neutral-100 box-border content-stretch flex gap-1 items-center justify-center px-2 py-1.5 relative rounded-lg shrink-0 w-[41px]"
                      data-name="Badge"
                      data-node-id="400:11437"
                    >
                      <div
                        aria-hidden="true"
                        className="absolute border border-[rgba(255,255,255,0)] border-solid inset-0 pointer-events-none rounded-lg"
                      />
                      <div
                        className="leading-[0] not-italic relative shrink-0 text-nowrap"
                        id="node-I400_11437-26_171"
                      >
                        <p className="font-['Inter'] font-semibold text-[12px] text-neutral-400 block leading-[16px] whitespace-pre">--</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="flex flex-row items-center self-stretch">
                  <div className="h-full w-px bg-gray-300"></div>
                </div>
                <div
                  className="basis-0 box-border content-stretch flex flex-col gap-3 grow items-start justify-center min-h-px min-w-px p-0 relative shrink-0"
                  data-name="Component 2"
                  data-node-id="400:10847"
                >
                  <div
                    className="leading-[0] not-italic relative shrink-0 text-nowrap"
                    data-node-id="400:10848"
                  >
                    <p className="font-['Inter'] font-medium text-[14px] text-gray-500 block leading-[20px] whitespace-pre">Total contacts</p>
                  </div>
                  <div
                    className="box-border content-stretch flex items-end justify-between p-0 relative shrink-0 w-full"
                    data-node-id="400:10849"
                  >
                    <div
                      className="leading-[0] not-italic relative shrink-0 text-nowrap"
                      data-node-id="400:10850"
                    >
                      <p className="font-['Inter'] font-bold text-[24px] text-neutral-950 block leading-none whitespace-pre">{formatNumber(parseIntSafe(dashboardData.dashboard_kpi.total_subscribed))}</p>
                    </div>
                    <div
                      className="bg-red-100 box-border content-stretch flex gap-1 items-center justify-center px-2 py-1.5 relative rounded-lg shrink-0"
                      data-name="Badge"
                      data-node-id="400:10889"
                    >
                      <div
                        aria-hidden="true"
                        className="absolute border border-[rgba(255,255,255,0)] border-solid inset-0 pointer-events-none rounded-lg"
                      />
                      <div
                        className="overflow-clip relative shrink-0 size-3"
                        data-name="Icon / ArrowDown"
                        id="node-I400_10889-17096_180398"
                      >
                        <img alt="" className="block max-w-none size-full" src={images.chartIconDashboard} />
                      </div>
                      <div
                        className="leading-[0] not-italic relative shrink-0 text-nowrap"
                        id="node-I400_10889-26_171"
                      >
                        <p className="font-['Inter'] font-semibold text-[12px] text-neutral-900 block leading-[16px] whitespace-pre">{formatPercentage(parseFloatSafe(dashboardData.dashboard_kpi.unsubscribe_percentage_growth))}</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="flex flex-row items-center self-stretch">
                  <div className="h-full w-px bg-gray-300"></div>
                </div>
                <div
                  className="basis-0 box-border content-stretch flex flex-col gap-3 grow items-start justify-center min-h-px min-w-px p-0 relative shrink-0"
                  data-name="Component 2"
                  data-node-id="400:10863"
                >
                  <div
                    className="leading-[0] not-italic relative shrink-0 text-nowrap"
                    data-node-id="400:10864"
                  >
                    <p className="font-['Inter'] font-medium text-[14px] text-gray-500 block leading-[20px] whitespace-pre">Email Opens</p>
                  </div>
                  <div
                    className="box-border content-stretch flex items-end justify-between p-0 relative shrink-0 w-full"
                    data-node-id="400:10865"
                  >
                    <div
                      className="leading-[0] not-italic relative shrink-0 text-nowrap"
                      data-node-id="400:10866"
                    >
                      <p className="font-['Inter'] font-bold text-[24px] text-neutral-950 block leading-none whitespace-pre">{formatNumber(parseIntSafe(dashboardData.dashboard_kpi.total_email_opens))}</p>
                    </div>
                    <div
                      className="bg-green-100 box-border content-stretch flex gap-1 items-center justify-center px-2 py-1.5 relative rounded-lg shrink-0"
                      data-name="Badge"
                      data-node-id="400:10883"
                    >
                      <div
                        aria-hidden="true"
                        className="absolute border border-[rgba(255,255,255,0)] border-solid inset-0 pointer-events-none rounded-lg"
                      />
                      <div
                        className="overflow-clip relative shrink-0 size-3"
                        data-name="Icon / ArrowUp"
                        id="node-I400_10883-17096_180398"
                      >
                        <img alt="" className="block max-w-none size-full" src={images.trendingUpIcon} />
                      </div>
                      <div
                        className="leading-[0] not-italic relative shrink-0 text-nowrap"
                        id="node-I400_10883-26_171"
                      >
                        <p className="font-['Inter'] font-semibold text-[12px] text-neutral-900 block leading-[16px] whitespace-pre">{formatPercentage(parseFloatSafe(dashboardData.dashboard_kpi.open_percentage_growth))}</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="flex flex-row items-center self-stretch">
                  <div className="h-full w-px bg-gray-300"></div>
                </div>
                <div
                  className="basis-0 box-border content-stretch flex flex-col gap-3 grow items-start justify-center min-h-px min-w-px p-0 relative shrink-0"
                  data-name="Component 2"
                  data-node-id="400:10871"
                >
                  <div
                    className="leading-[0] not-italic relative shrink-0 text-nowrap"
                    data-node-id="400:10872"
                  >
                    <p className="font-['Inter'] font-medium text-[14px] text-gray-500 block leading-[20px] whitespace-pre">Open Rate</p>
                  </div>
                  <div
                    className="box-border content-stretch flex items-end justify-between p-0 relative shrink-0 w-full"
                    data-node-id="400:10873"
                  >
                    <div
                      className="leading-[0] not-italic relative shrink-0 text-nowrap"
                      data-node-id="400:10874"
                    >
                      <p className="font-['Inter'] font-bold text-[24px] text-neutral-950 block leading-none whitespace-pre">{formatPercentage(parseFloatSafe(dashboardData.dashboard_kpi.avg_open_rate))}</p>
                    </div>
                    <div
                      className="bg-yellow-100 box-border content-stretch flex gap-1 items-center justify-center px-2 py-1.5 relative rounded-lg shrink-0"
                      data-name="Badge"
                      data-node-id="400:10901"
                    >
                      <div
                        aria-hidden="true"
                        className="absolute border border-[rgba(255,255,255,0)] border-solid inset-0 pointer-events-none rounded-lg"
                      />
                      <div
                        className="leading-[0] not-italic relative shrink-0 text-nowrap"
                        id="node-I400_10901-26_171"
                      >
                        <p className="font-['Inter'] font-semibold text-[12px] text-neutral-900 block leading-[16px] whitespace-pre">No change</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="flex flex-row items-center self-stretch">
                  <div className="h-full w-px bg-gray-300"></div>
                </div>
                <div
                  className="basis-0 box-border content-stretch flex flex-col gap-3 grow items-start justify-center min-h-px min-w-px p-0 relative shrink-0"
                  data-name="Component 2"
                  data-node-id="400:10672"
                >
                  
                  <div
                    className="leading-[0] not-italic relative shrink-0 text-nowrap"
                    data-node-id="400:10673"
                  >
                    { dashboardData?.plan && dashboardData.plan === 'pro' && <p className="font-['Inter'] font-medium text-[14px] text-gray-500 block leading-[20px] whitespace-pre">Click Rate</p> }
                    { dashboardData?.plan && dashboardData.plan !== 'pro' && <a href={getPricingPageUrl()} target="_blank"><p className="font-['Inter'] font-medium text-[14px] text-gray-500 block leading-[20px] whitespace-pre">Click Rate <img alt="" className="w-6 h-6 ml-1 inline-block" src={images.workflowIcon} /></p></a> }
                  </div>
                  <div
                    className="box-border content-stretch flex items-end justify-between p-0 relative shrink-0 w-full"
                    data-node-id="400:10674"
                  >
                    <div
                      className="leading-[0] not-italic relative shrink-0 text-nowrap"
                      data-node-id="400:10675"
                    >
                      { dashboardData?.plan && dashboardData.plan === 'pro' && <p className="font-['Inter'] font-bold text-[24px] text-neutral-950 block leading-none whitespace-pre">{formatPercentage(parseFloatSafe(dashboardData.dashboard_kpi.avg_click_rate))}</p> }
                      { dashboardData?.plan && dashboardData.plan !== 'pro' && <p className="font-['Inter'] font-bold text-[24px] text-neutral-950 block leading-none whitespace-pre">0%</p> }
                    </div>
                    <div
                      className="bg-green-100 box-border content-stretch flex gap-1 items-center justify-center px-2 py-1.5 relative rounded-lg shrink-0"
                      data-name="Badge"
                      data-node-id="400:10838"
                    >
                      <div
                        aria-hidden="true"
                        className="absolute border border-[rgba(255,255,255,0)] border-solid inset-0 pointer-events-none rounded-lg"
                      />
                      <div
                        className="overflow-clip relative shrink-0 size-3"
                        data-name="Icon / ArrowUp"
                        id="node-I400_10838-17096_180398"
                      >

                        <img alt="" className="block max-w-none size-full" src={images.trendingUpIcon} />
                      </div>
                      <div
                        className="leading-[0] not-italic relative shrink-0 text-nowrap"
                        id="node-I400_10838-26_171"
                      >
                        <p className="font-['Inter'] font-medium text-[12px] text-neutral-900 block leading-[16px] whitespace-pre">{formatPercentage(parseFloatSafe(dashboardData.dashboard_kpi.click_percentage_growth))}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div
              aria-hidden="true"
              className="absolute border border-[rgba(255,255,255,0)] border-solid inset-0 pointer-events-none rounded-xl"
            />
          </div>
        </div>

        <div 
          className="box-border content-stretch flex flex-col gap-4 items-start justify-start p-0 relative w-full"
          data-node-id="58:7272"
        >
          <div
            className="leading-[0] not-italic relative shrink-0 w-full"
          >
            <p className="font-['Inter'] font-semibold text-[18px] text-neutral-950 block leading-[28px]">Campaigns</p>
          </div>
          
          <div
            className="bg-[#ffffff] box-border content-stretch flex flex-col items-start justify-start p-[16px] relative w-full rounded-xl border border-neutral-200"
            data-name="Table"
            data-node-id="60:11292"
          >
            {(() => {
              return null;
            })()}
            {isLoading ? (
              <div className="flex items-center justify-center py-12 w-full">
                <div className="text-gray-500">Loading campaigns...</div>
              </div>
            ) : dashboardData?.campaigns && dashboardData.campaigns.length > 0 ? (
              <div
                className="box-border content-stretch flex items-start justify-start overflow-clip p-0 relative shrink-0 w-full"
                data-name="Columns"
                id="node-I60_11292-324_359"
              >
                <div
                  className="basis-0 box-border content-stretch flex flex-col grow items-start justify-start min-h-px min-w-px overflow-clip p-0 relative shrink-0"
                  data-name="Column"
                  id="node-I60_11292-324_348"
                >
                  <div
                    className="bg-slate-100 box-border content-stretch flex h-10 items-center justify-between min-w-[85px] px-4 py-1.5 relative rounded-bl-[8px] rounded-tl-[8px] shrink-0 w-full"
                    data-name="Table / Head"
                    id="node-I60_11292-324_296"
                  >
                    <div
                      className="basis-0 flex flex-col  grow h-full justify-center leading-[0] min-h-px min-w-px not-italic relative shrink-0"
                      id="node-I60_11292-324_296-190_892"
                    >
                      <p className="block font-['Inter'] font-medium leading-[16px] text-[12px] text-neutral-500">Campaign Name</p>
                    </div>
                  </div>
                  {dashboardData.dashboard_kpi.campaigns.slice(0, 5).map((campaign) => {
                    // Generate appropriate URL based on campaign hash
                    const campaignUrl = `${getBaseUrl()}/wp-admin/admin.php?page=es_campaigns#!/campaign/edit/${campaign.id}`;

                    return (
                      <a
                        key={campaign.id}
                        href={campaignUrl}
                        className="block cursor-pointer"
                      >
                        <div
                          className="box-border content-stretch flex gap-2 h-[52px] items-center justify-start min-w-[85px] px-4 py-2 relative shrink-0 w-full"
                          data-name="Table / Cell"
                        >
                          <div
                            aria-hidden="true"
                            className="absolute border-b border-neutral-200 inset-0 pointer-events-none"
                          />
                          <div
                            className="basis-0 box-border content-stretch flex flex-col grow items-start justify-start min-h-px min-w-px p-0 relative shrink-0"
                            data-name="Text Wrapper"
                          >
                            <div
                              className="flex flex-col font-['Inter'] font-medium justify-center leading-[0] not-italic overflow-ellipsis overflow-hidden relative shrink-0 text-[14px] text-neutral-950 text-nowrap w-full"
                            >
                              <span className="[text-overflow:inherit] [text-wrap-mode:inherit] [white-space-collapse:inherit] block leading-[20px] overflow-inherit hover:text-violet-600 transition-colors">
                                {campaign.title || 'Untitled Campaign'}
                              </span>
                            </div>
                          </div>
                        </div>
                      </a>
                    );
                  })}
                </div>

                <div
                  className="basis-0 box-border content-stretch flex flex-col grow items-start justify-start min-h-px min-w-px overflow-clip p-0 relative shrink-0"
                  data-name="Column"
                  id="node-I60_11292-324_349"
                >
                  <div
                    className="bg-slate-100 box-border content-stretch flex h-10 items-center justify-between min-w-[85px] px-4 py-1.5 relative shrink-0 w-full"
                    data-name="Table / Head"
                    id="node-I60_11292-324_309"
                  >
                    <div
                      className="basis-0 flex flex-col  grow h-full justify-center leading-[0] min-h-px min-w-px not-italic relative shrink-0"
                      id="node-I60_11292-324_309-190_892"
                    >
                      <p className="block font-['Inter'] font-medium leading-[16px] text-[12px] text-neutral-500">Sent</p>
                    </div>
                  </div>
                  {dashboardData.dashboard_kpi.campaigns.slice(0, 5).map((campaign) => (
                    <div
                      key={campaign.hash}
                      className="box-border content-stretch flex h-[52px] items-center justify-start min-w-[85px] px-4 py-2 relative shrink-0 w-full"
                      data-name="Table / Cell"
                    >
                      <div
                        aria-hidden="true"
                        className="absolute border-b border-neutral-200 inset-0 pointer-events-none"
                      />
                      <div
                        className="basis-0 flex flex-col  grow justify-center leading-[0] min-h-px min-w-px not-italic overflow-ellipsis overflow-hidden relative shrink-0"
                      >
                        <p className="[text-overflow:inherit] [text-wrap-mode:inherit] [white-space-collapse:inherit] block leading-[20px] overflow-inherit font-['Inter'] font-normal text-[14px] text-neutral-950 text-nowrap">
                          {formatNumber(parseIntSafe(campaign.total_sent) || 0)}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>

                <div
                  className="basis-0 box-border content-stretch flex flex-col grow items-start justify-start min-h-px min-w-px overflow-clip p-0 relative shrink-0"
                  data-name="Column"
                  id="node-I60_11292-324_356"
                >
                  <div
                    className="bg-slate-100 box-border content-stretch flex h-10 items-center justify-between min-w-[85px] px-4 py-1.5 relative shrink-0 w-full"
                    data-name="Table / Head"
                    id="node-I60_11292-324_322"
                  >
                    <div
                      className="basis-0 flex flex-col grow h-full justify-center leading-[0] min-h-px min-w-px not-italic relative shrink-0"
                      id="node-I60_11292-324_322-190_892"
                    >
                      <p className="block font-['Inter'] font-medium leading-[16px] text-[12px] text-neutral-500">Open Rate</p>
                    </div>
                  </div>
                  {dashboardData.dashboard_kpi.campaigns.slice(0, 5).map((campaign) => {
                    const totalSent = parseIntSafe(campaign.total_sent || 0);
                    const totalOpens = parseIntSafe(campaign.total_opens || 0);
                    const openRate = totalSent > 0 
                      ? ((totalOpens / totalSent) * 100).toFixed(1)
                      : '0';
                    
                    return (
                    <div
                      key={campaign.hash}
                      className="box-border content-stretch flex h-[52px] items-center justify-start min-w-[85px] px-4 py-2 relative shrink-0 w-full"
                      data-name="Table / Cell"
                    >
                      <div
                        aria-hidden="true"
                        className="absolute border-b border-neutral-200 inset-0 pointer-events-none"
                      />
                      <div
                        className="basis-0 flex flex-col  grow justify-center leading-[0] min-h-px min-w-px not-italic overflow-ellipsis overflow-hidden relative shrink-0"
                      >
                        <p className="[text-overflow:inherit] [text-wrap-mode:inherit] [white-space-collapse:inherit] block leading-[20px] overflow-inherit font-['Inter'] font-normal text-[14px] text-neutral-950 text-nowrap">
                          {openRate}%
                        </p>
                      </div>
                    </div>
                    );
                  })}
                </div>

                <div
                  className="basis-0 box-border content-stretch flex flex-col grow items-start justify-start min-h-px min-w-px overflow-clip p-0 relative shrink-0"
                  data-name="Column"
                  id="node-I60_11292-336_6220"
                >
                  <div
                    className="bg-slate-100 box-border content-stretch flex h-10 items-center justify-between min-w-[85px] px-4 py-1.5 relative shrink-0 w-full"
                    data-name="Table / Head"
                    id="node-I60_11292-336_6221"
                  >
                    <div
                      className="basis-0 flex flex-col grow h-full justify-center leading-[0] min-h-px min-w-px not-italic relative shrink-0"
                      id="node-I60_11292-336_6221-190_892"
                    >
                      <p className="block leading-[16px] font-['Inter'] font-medium text-[12px] text-neutral-500">Click Rate</p>
                    </div>
                  </div>
                  {dashboardData.dashboard_kpi.campaigns.slice(0, 5).map((campaign) => {
                    const totalSent = parseIntSafe(campaign.total_sent || 0);
                    const totalClicks = parseIntSafe(campaign.total_clicks || 0);
                    const clickRate = totalSent > 0 
                      ? ((totalClicks / totalSent) * 100).toFixed(1)
                      : '0';
                    
                    return (
                    <div
                      key={campaign.hash}
                      className="box-border content-stretch flex h-[52px] items-center justify-start min-w-[85px] px-4 py-2 relative shrink-0 w-full"
                      data-name="Table / Cell"
                    >
                      <div
                        aria-hidden="true"
                        className="absolute border-b border-neutral-200 inset-0 pointer-events-none"
                      />
                      <div
                        className="basis-0 flex flex-col grow justify-center leading-[0] min-h-px min-w-px not-italic overflow-ellipsis overflow-hidden relative shrink-0"
                      >
                        <p className="[text-overflow:inherit] [text-wrap-mode:inherit] [white-space-collapse:inherit] block leading-[20px] overflow-inherit font-['Inter'] font-normal text-[14px] text-neutral-950 text-nowrap">
                          {clickRate}%
                        </p>
                      </div>
                    </div>
                    );
                  })}
                </div>

                <div
                  className="box-border content-stretch flex flex-col items-start justify-start overflow-clip p-0 relative shrink-0"
                  data-name="Column"
                  id="node-I60_11292-324_357"
                >
                  <div
                    className="bg-slate-100 box-border content-stretch flex h-10 items-center justify-between min-w-[85px] px-4 py-1.5 relative rounded-br-[8px] rounded-tr-[8px] shrink-0 w-full"
                    data-name="Table / Head"
                    id="node-I60_11292-324_335"
                  >
                    <div
                      className="basis-0 flex flex-col grow h-full justify-center leading-[0] min-h-px min-w-px not-italic relative shrink-0"
                      id="node-I60_11292-324_335-190_892"
                    >
                      <p className="block leading-[20px] font-['Inter'] font-medium text-[14px] text-neutral-500">Status</p>
                    </div>
                  </div>
                  {dashboardData.dashboard_kpi.campaigns.slice(0, 5).map((campaign) => {
                    const getStatusDetails = (status: string) => {
                      if (status === '5') {
                        return {
                          icon: sentStatusIcon,
                          text: 'Sent',
                          color: 'text-green-600'
                        };
                      } else if (status === '1') {
                        return {
                          icon: activeStatusIcon,
                          text: 'Active',
                          color: 'text-green-600'
                        };
                      } else if (status === '2') {
                        return {
                          icon: scheduledStatusIcon,
                          text: 'Scheduled',
                          color: 'text-purple-600'
                        };
                      } else if (status === '3') {
                        return {
                          icon: sendingStatusIcon,
                          text: 'Sending',
                          color: 'text-orange-600'
                        };
                      } else if (status === '4') {
                        return {
                          icon: pausedStatusIcon,
                          text: 'Paused',
                          color: 'text-yellow-600'
                        };
                      } else if (status === '0' || status === '') {
                        return {
                          icon: draftStatusIcon,
                          text: 'Draft',
                          color: 'text-blue-600'
                        };
                      } else {
                        return {
                          icon: inactiveStatusIcon,
                          text: status || 'Unknown',
                          color: 'text-gray-600'
                        };
                      }
                    };

                    const statusDetails = getStatusDetails(campaign.status || '');

                    return (
                      <div
                        key={campaign.hash}
                        className="box-border content-stretch flex gap-3 h-[52px] items-center justify-start min-w-[85px] px-4 py-2 relative shrink-0 w-full"
                        data-name="Table / Cell"
                      >
                        <div
                          aria-hidden="true"
                          className="absolute border-b border-neutral-200 inset-0 pointer-events-none"
                        />
                        <div className="relative shrink-0 size-3.5">
                          <img alt="" className="block max-w-none size-full" src={statusDetails.icon} />
                        </div>
                        <div
                          className={`basis-0 flex flex-col grow justify-center leading-[0] min-h-px min-w-px not-italic overflow-ellipsis overflow-hidden relative shrink-0 ${statusDetails.color}`}
                        >
                          <p className="[text-overflow:inherit] [text-wrap-mode:inherit] [white-space-collapse:inherit] block leading-[20px] overflow-inherit font-['Inter'] font-normal text-[14px] text-nowrap">
                            {statusDetails.text}
                          </p>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            ) : (
              /* Empty State */
              <div className="flex flex-col items-center justify-center py-12 px-4 w-full">
                <div className="text-center">
                  <div className="text-gray-400 mb-4">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1" className="mx-auto">
                      <path d="M3 3h18v18H3z"/>
                      <path d="M8 12h8"/>
                      <path d="M8 16h6"/>
                    </svg>
                  </div>
                  <h3 className="text-lg font-medium text-gray-900 mb-2 font-['Inter']">No campaigns yet</h3>
                  <p className="text-gray-500 mb-4 font-['Inter']">Create your first email campaign to get started.</p>
                  <a 
                    href={`${getBaseUrl()}/wp-admin/admin.php?page=es_campaigns#!/gallery?campaignType=newsletter`}
                    className="inline-flex items-center gap-2 px-4 py-2 font-['Inter'] font-medium text-[#5e19cf] text-[14px] leading-[20px] whitespace-nowrap rounded-lg border border-[#5e19cf]"
                  >
                    Create Campaign
                  </a>
                </div>
              </div>
            )}

            {/* View All Button - Only show if there are campaigns */}
            {dashboardData?.campaigns && dashboardData.campaigns.length > 0 && (
              <div
                className="box-border content-stretch flex gap-2.5 h-9 items-center justify-center pb-0 pt-4 px-0 relative shrink-0 w-full"
                data-name="_TableCaption"
                id="node-I60_11292-324_363"
              >
                <a
                  href={`${getBaseUrl()}/wp-admin/admin.php?page=es_campaigns#!/campaign`}
                  className="box-border content-stretch flex gap-2.5 items-center justify-start p-0 relative shrink-0 rounded-lg px-2 py-1 transition-colors"
                  id="node-I60_11292-24013_25692"
                >
                  <div
                    className="flex flex-col justify-center leading-[0] not-italic relative shrink-0"
                    id="node-I60_11292-324_364"
                  >
                    <p className="block leading-[20px] whitespace-pre font-['Inter'] font-normal text-[14px] text-center text-neutral-900 text-nowrap">View All</p>
                  </div>
                  <div
                    className="overflow-clip relative shrink-0 size-6"
                    data-name="Icon / ChevronRight"
                    id="node-I60_11292-24013_25600"
                  >
                    <div
                      className="absolute bottom-1/4 left-[37.5%] right-[37.5%] top-1/4"
                      data-name="Vector"
                      id="node-I60_11292-24013_25600-5197_627"
                    >
                      <div
                        className="absolute inset-[-8.33%_-16.67%]"
                        style={{ "--stroke-0": "rgba(23, 23, 23, 1)" } as React.CSSProperties}
                      >
                        <img alt="" className="block max-w-none size-full" src={tableChevronRight} />
                      </div>
                    </div>
                  </div>
                </a>
              </div>
            )}
          </div>
        </div>

        {/* Audiences and Forms Section */}
        <div className="flex gap-4 w-full max-w-full">
          {/* Audiences */}
          <div className="flex-1">
            <h2 className="text-lg font-semibold text-neutral-950 mb-3">Audiences</h2>
            <div
              className="bg-[#ffffff] relative rounded-xl w-full"
              data-name="navigation menu content item"
              data-node-id="58:7355"
            >
              <div className="box-border content-stretch flex flex-col gap-6 items-start justify-center overflow-clip p-[24px] relative w-full">
                <div
                  className="box-border content-stretch flex gap-[23px] items-start justify-start p-0 relative shrink-0"
                  data-node-id="60:20305"
                >
                  <div
                    className="box-border content-stretch flex gap-2 items-center justify-start p-0 relative shrink-0"
                    data-node-id="60:20306"
                  >
                    <div
                      className="leading-[0] not-italic relative shrink-0"
                      data-node-id="60:20308"
                    >
                      <p className="block leading-[24px] whitespace-pre font-['Inter'] font-semibold text-[16px] text-neutral-950 text-nowrap">Monthly Growth</p>
                    </div>
                  </div>
                </div>
              <div className="w-full h-[275.2px]">
                <SimpleAreaChart DashboardData={chartData}/>
              </div>
              </div>
              <div
                aria-hidden="true"
                className="absolute border border-[rgba(163,163,163,0.01)] border-solid inset-0 pointer-events-none rounded-xl"
              />
            </div>
          </div>

          {/* Forms */}
          <div className="flex-1">
            <div
              className="box-border content-stretch flex flex-col gap-4 items-center justify-start p-0 relative w-full"
              data-node-id="58:7403"
            >
              <div
                className="leading-[0] not-italic relative shrink-0 w-full"
                data-node-id="58:7404"
              >
                <p className="font-['Inter'] font-semibold text-[18px] text-neutral-950 block leading-[28px]">Forms</p>
              </div>
              
              {/* Forms Table - Exact Figma Design Implementation */}
              <div
                className="bg-[#ffffff] box-border content-stretch flex flex-col items-start justify-start p-[16px] relative w-full rounded-xl"
                data-name="Table"
                data-node-id="60:18026"
              >
                {dashboardData?.forms && dashboardData.forms.length > 0 ? (
                  <>
                    <div
                      className="box-border content-stretch flex items-center justify-start p-0 relative shrink-0 w-full"
                      data-name="Columns"
                      id="node-I60_18026-324_359"
                    >
                      {/* Form Name Column */}
                      <div
                        className="basis-0 box-border content-stretch flex flex-col grow items-start justify-start min-h-px min-w-px p-0 relative shrink-0"
                        data-name="Column"
                        id="node-I60_18026-324_348"
                      >
                        <div
                          className="bg-slate-100 box-border content-stretch flex h-10 items-center justify-between min-w-[85px] px-4 py-1.5 relative rounded-bl-[8px] rounded-tl-[8px] shrink-0 w-full"
                          data-name="Table / Head"
                          id="node-I60_18026-324_296"
                        >
                          <div
                            className="basis-0 flex flex-col grow h-full justify-center leading-[0] min-h-px min-w-px not-italic relative shrink-0"
                            id="node-I60_18026-324_296-190_892"
                          >
                            <p className="block leading-[16px] font-['Inter'] font-medium text-[12px] text-neutral-500">Form Name</p>
                          </div>
                        </div>
                        
                        {/* Dynamic Form Name Cells */}
                        {dashboardData.forms.slice(0, 5).map((form) => (
                          <div
                            key={form.id}
                            className="box-border content-stretch flex gap-2 h-[52px] items-center justify-start min-w-[85px] px-4 py-2 relative shrink-0 w-full"
                            data-name="Table / Cell"
                          >
                            <div
                              aria-hidden="true"
                              className="absolute border-b border-neutral-200 inset-0 pointer-events-none"
                            />
                            <div
                              className="basis-0 box-border content-stretch flex flex-col grow items-start justify-start min-h-px min-w-px p-0 relative shrink-0"
                              data-name="Text Wrapper"
                            >
                              <a
                                href={`${getBaseUrl()}/wp-admin/admin.php?page=es_forms&action=edit&form=${form.id}`}
                                className="flex flex-col justify-center leading-[0] not-italic overflow-ellipsis overflow-hidden relative shrink-0 w-full"
                              >
                                <p className="[text-overflow:inherit] [text-wrap-mode:inherit] [white-space-collapse:inherit] block leading-[20px] overflow-inherit font-['Inter'] font-medium text-[14px] text-neutral-950 text-nowrap hover:text-violet-600 transition-colors">
                                  {form.name}
                                </p>
                              </a>
                            </div>
                          </div>
                        ))}
                      </div>
                      
                      {/* Total Subscriptions Column */}
                      <div
                        className="box-border content-stretch flex flex-col items-start justify-start p-0 relative shrink-0"
                        data-name="Column"
                        id="node-I60_18026-324_349"
                      >
                        <div
                          className="bg-slate-100 box-border content-stretch flex h-10 items-center justify-between min-w-[85px] px-4 py-1.5 relative shrink-0 w-[177.2px]"
                          data-name="Table / Head"
                          id="node-I60_18026-324_309"
                        >
                          <div
                            className="basis-0 flex flex-col  grow h-full justify-center leading-[0] min-h-px min-w-px not-italic relative shrink-0"
                            id="node-I60_18026-324_309-190_892"
                          >
                            <p className="block leading-[16px] font-['Inter'] font-medium text-[12px] text-neutral-500">Total Subscriptions</p>
                          </div>
                        </div>
                        
                        {/* Dynamic Total Subscriptions Cells */}
                        {dashboardData.forms.slice(0, 5).map((form) => {
                          const subscriberCount = form.subscriber_count || 0;
                          
                          return (
                            <div
                              key={`subscriber-${form.id}`}
                              className="box-border content-stretch flex h-[52px] items-center justify-start min-w-[85px] px-4 py-2 relative shrink-0 w-[177.2px]"
                              data-name="Table / Cell"
                            >
                              <div
                                aria-hidden="true"
                                className="absolute border-b border-neutral-200 inset-0 pointer-events-none"
                              />
                              <div
                                className="basis-0 flex flex-col grow justify-center leading-[0] min-h-px min-w-px not-italic overflow-ellipsis overflow-hidden relative shrink-0"
                              >
                                <p className="[text-overflow:inherit] [text-wrap-mode:inherit] [white-space-collapse:inherit] block leading-[20px] overflow-inherit font-['Inter'] font-normal text-[14px] text-nowrap text-neutral-950">
                                  {subscriberCount.toLocaleString()}
                                </p>
                              </div>
                            </div>
                          );
                        })}
                      </div>
                      
                      {/* Form Lists Column */}
                      <div
                        className="box-border content-stretch flex flex-col items-start justify-start p-0 relative shrink-0"
                        data-name="Column"
                        id="node-I60_18026-324_357"
                      >
                        <div
                          className="bg-slate-100 box-border content-stretch flex h-10 items-center justify-between min-w-[85px] px-4 py-1.5 relative rounded-br-[8px] rounded-tr-[8px] shrink-0 w-full"
                          data-name="Table / Head"
                          id="node-I60_18026-324_335"
                        >
                          <div
                            className="basis-0 flex flex-col grow h-full justify-center leading-[0] min-h-px min-w-px not-italic relative shrink-0"
                            id="node-I60_18026-324_335-190_892"
                          >
                            <p className="block leading-[20px] font-['Inter'] font-medium text-[14px] text-neutral-500">Form Lists</p>
                          </div>
                        </div>
                        
                        {/* Dynamic Form Lists Cells */}
                        {dashboardData.forms.slice(0, 5).map((form) => {
                          const listNamesString = form.list_names || '';
                          const lists = listNamesString.split(',').map(name => name.trim()).filter(name => name.length > 0);
                          const displayLists = lists.slice(0, 2);
                          const hasMoreLists = lists.length > 2;
                          
                          return (
                            <div
                              key={`lists-${form.id}`}
                              className="box-border content-stretch flex gap-3 h-[52px] items-center justify-start min-w-[85px] px-4 py-2 relative shrink-0 w-full"
                              data-name="Table / Cell"
                            >
                              <div
                                aria-hidden="true"
                                className="absolute border-b border-neutral-200 inset-0 pointer-events-none"
                              />
                              <div
                                className="basis-0 flex flex-col grow justify-center leading-[0] min-h-px min-w-px not-italic overflow-ellipsis overflow-hidden relative shrink-0"
                              >
                                {displayLists.length > 0 ? (
                                  <p className="[text-overflow:inherit] [text-wrap-mode:inherit] [white-space-collapse:inherit] block leading-[20px] overflow-inherit font-['Inter'] font-normal text-[14px] text-nowrap text-neutral-950">
                                    {displayLists.join(', ')}{hasMoreLists ? '...' : ''}
                                  </p>
                                ) : (
                                  <p className="[text-overflow:inherit] [text-wrap-mode:inherit] [white-space-collapse:inherit] block leading-[20px] overflow-inherit text-gray-400 font-['Inter'] font-normal text-[14px] text-nowrap">
                                    No lists
                                  </p>
                                )}
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    </div>
                    
                    {/* View All Button - Only show if there are forms */}
                    <div
                      className="box-border content-stretch flex gap-2.5 h-9 items-center justify-center pb-0 pt-4 px-0 relative shrink-0 w-full"
                      data-name="_TableCaption"
                      id="node-I60_18026-324_363"
                    >
                      <a
                        href={`${getBaseUrl()}/wp-admin/admin.php?page=es_forms`}
                        className="box-border content-stretch flex gap-2.5 items-center justify-start p-0 relative shrink-0 rounded-lg px-2 py-1 transition-colors"
                        id="node-I60_18026-24013_25692"
                      >
                        <div
                          className="flex flex-col justify-center leading-[0] not-italic relative shrink-0"
                          id="node-I60_18026-324_364"
                        >
                          <p className="block leading-[20px] whitespace-pre font-['Inter'] font-normal text-[14px] text-center text-neutral-900 text-nowrap">View All</p>
                        </div>
                        <div
                          className="overflow-clip relative shrink-0 size-6"
                          data-name="Icon / ChevronRight"
                          id="node-I60_18026-24013_25600"
                        >
                          <div
                            className="absolute bottom-1/4 left-[37.5%] right-[37.5%] top-1/4"
                            data-name="Vector"
                            id="node-I60_18026-24013_25600-5197_627"
                          >
                            <div
                              className="absolute inset-[-8.33%_-16.67%]"
                              style={{ "--stroke-0": "rgba(23, 23, 23, 1)" } as React.CSSProperties}
                            >
                              <img alt="" className="block max-w-none size-full" src={formsChevronRightIcon} />
                            </div>
                          </div>
                        </div>
                      </a>
                    </div>
                  </>
                ) : (
                  /* Empty State for Forms */
                  <div className="flex items-center justify-center py-12 w-full">
                    <div className="text-center">
                      <h3 className="text-lg font-semibold text-neutral-950 mb-2 font-['Inter']">You have not created any forms</h3>
                      <p className="text-sm text-gray-500 mb-6 font-['Inter']">Start creating your first form</p>
                      <a
                        href={`${getBaseUrl()}/wp-admin/admin.php?page=es_forms&action=new`}
                        className="inline-flex items-center gap-2 px-4 py-2 font-['Inter'] font-medium text-[#5e19cf] text-[14px] leading-[20px] whitespace-nowrap rounded-lg border border-[#5e19cf]"
                      >
                        <span>+</span>
                        Create form
                      </a>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Workflows Section */}
        <div className="flex flex-col gap-3 w-full">
          <div className="flex gap-2 items-center">
            <h2 className="text-lg font-semibold text-neutral-950 ">Workflows</h2>
            <img alt="" className="w-6 h-6" src={images.workflowIcon} />
          </div>

          <div className="bg-white rounded-xl p-4 border border-gray-50">
            <div 
              className="font-['Inter'] font-normal text-[16px] text-neutral-950 leading-[24px] mb-4"
              data-node-id="62:6872"
            >
              Preview a popular pre-built workflow
            </div>
            
            {/* Divider line after heading */}
            <div className="w-full h-px bg-neutral-200 mb-4"></div>
            
            <div className="flex gap-2 items-start mb-4">
              <img alt="" className="w-6 h-6" src={images.workflowInfoIcon} />
              <p className="text-base font-medium text-neutral-500">
                You're on the <span className="text-violet-600">{(() => {
                  const userPlan = dashboardData?.plan;
                  return userPlan 
                    ? userPlan.charAt(0).toUpperCase() + userPlan.slice(1).toLowerCase()
                    : 'Free';
                })()}</span> Plan. Enjoy our pre-built user workflows.
              </p>
            </div>

            <div className="flex gap-4 mb-4">
              <a 
                href={`${getBaseUrl()}/wp-admin/admin.php?page=es_workflows&action=new`}
                className="bg-white border border-neutral-200 rounded-xl p-4 flex-1 h-32 flex flex-col justify-center hover:border-violet-300 hover:shadow-md transition-all duration-200 cursor-pointer text-decoration-none"
              >
                <img alt="" className="w-6 h-6 mb-4" src={images.workflowBuildIcon} />
                <h3 className="text-base font-semibold text-violet-600 font-['Inter']">
                  Build Workflow From Scratch
                </h3>
              </a>

              <div 
                onClick={() => {
                  handleWorkflowCreate('welcome-email');
                }}
                className={`bg-white border border-neutral-200 rounded-xl p-4 flex-1 h-32 flex flex-col justify-center hover:border-violet-300 hover:shadow-md transition-all duration-200 cursor-pointer ${
                  workflowCreating === 'welcome-email' ? 'opacity-50 pointer-events-none' : ''
                }`}
              >
                <img alt="" className="w-6 h-6 mb-4" src={images.workflowWelcomeContactsIcon} />
                <h3 className="text-base font-semibold text-neutral-950 font-['Inter']">
                  {workflowCreating === 'welcome-email' ? 'Creating...' : 'New Subscriber Welcome email'}
                </h3>
              </div>

              <div 
                onClick={() => {
                  handleWorkflowCreate('confirmation-email');
                }}
                className={`bg-white border border-neutral-200 rounded-xl p-4 flex-1 h-32 flex flex-col justify-center hover:border-violet-300 hover:shadow-md transition-all duration-200 cursor-pointer ${
                  workflowCreating === 'confirmation-email' ? 'opacity-50 pointer-events-none' : ''
                }`}
              >
                <img alt="" className="w-6 h-6 mb-4" src={images.workflowSubscriberWelcomeIcon} />
                <h3 className="text-base font-semibold text-neutral-950 font-['Inter']">
                  {workflowCreating === 'confirmation-email' ? 'Creating...' : 'New Subscriber Confirmation email'}
                </h3>
              </div>

              <div 
                onClick={handleAbandonedCartClick}
                className="bg-white border border-neutral-200 rounded-xl p-4 flex-1 h-32 flex flex-col justify-center relative hover:border-violet-300 hover:shadow-md transition-all duration-200 cursor-pointer"
              >
                {(() => {
                  const userPlan = dashboardData?.plan || 'lite';
                  const canCreateWorkflow = canCreateAbandonedCartWorkflow(userPlan);
                  
                  // Show different badges based on user plan
                  if (!dashboardData) {
                    // Loading state - show Popular by default
                    return (
                      <div className="absolute top-0 right-0 px-3 py-1.5 rounded-bl-lg rounded-tr-lg" style={{ background: '#DCFCE7' }}>
                        <span className="text-xs font-medium text-black-700 font-['Inter']">Popular</span>
                      </div>
                    );
                  }
                  
                  if (canCreateWorkflow) {
                    // Pro user - show Premium badge
                    return (
                      <div className="absolute top-0 right-0 px-3 py-1.5 rounded-bl-lg rounded-tr-lg" style={{ background: '#DCFCE7' }}>
                        <span className="text-xs font-medium text-black-700 font-['Inter']">Popular</span>
                      </div>
                    );
                  } else {
                    // Free user - show Popular badge
                    return (
                      <div className="absolute top-0 right-0 px-3 py-1.5 rounded-bl-lg rounded-tr-lg" style={{ background: '#DCFCE7' }}>
                        <span className="text-xs font-medium text-black-700 font-['Inter']">Premium</span>
                      </div>
                    );
                  }
                })()}
                <img alt="" className="w-6 h-6 mb-4" src={images.workflowTaggedCustomersIcon} />
                <h3 className="text-base font-semibold text-neutral-950 font-['Inter']">
                  {workflowCreating === 'abandoned-cart' ? 'Creating...' : 'Abandoned cart email'}
                </h3>
              </div>
            </div>

            <div className="flex items-center justify-center">
              <a 
                href={`${getBaseUrl()}/wp-admin/admin.php?page=es_workflows&tab=gallery`}
                className="px-2.5 py-1.5 rounded-lg text-sm font-medium text-neutral-900 flex items-center gap-1 transition-colors duration-200 cursor-pointer text-decoration-none font-['Inter']"
              >
                View All
                <img alt="" className="w-5 h-5" src={images.workflowChevronRightIcon} />
              </a>
            </div>
          </div>
        </div>

        {/* Help & Support Section */}
        <div className="flex flex-col gap-3 w-full">
          <h2 className="text-lg font-semibold text-neutral-950 font-['Inter']">Help & Support</h2>

          <div className="bg-white py-6 overflow-hidden">
            <div className="px-6 max-w-[1280px] mx-auto">
              <div className="flex gap-10">
                <div className="w-[440px] flex flex-col gap-4">
                  <div className="flex gap-1 items-center">
                    <img alt="" className="w-7 h-7" src={images.helpMessageCircleQuestionIcon} />
                    <h3 className="text-lg font-semibold text-neutral-950">
                      Frequently asked questions
                    </h3>
                  </div>
                  <p className="text-sm font-medium text-neutral-500 font-['Inter']">
                    We've compiled the most important information to help you get the most out of your experience. 
                    Can't find what you're looking for? <span className="text-neutral-900">Contact us</span>.
                  </p>
                  <a 
                    href="mailto:hello@icegram.com?subject=Support Request - Icegram Express"
                    className="border px-4 py-2 rounded-lg text-sm font-medium shadow-sm flex items-center gap-2 w-fit transition-colors duration-200 text-decoration-none" style={{"color": "#5E19CF", "borderColor": "#5E19CF"}}
                  >
                    <img alt="" className="w-4 h-4" src={images.helpMailIcon} />
                    Email us
                  </a>
                </div>

                <div className="w-px bg-gray-200"></div>

                <div className="flex-1 flex flex-col gap-4">
                  <div className="flex flex-col gap-2">
                    <div className="flex gap-2.5 items-center">
                      <img alt="" className="w-6 h-6" src={images.helpCircleHelpIcon} />
                      <h3 className="text-xl font-semibold text-neutral-950 font-['Inter']">FAQ</h3>
                    </div>
                    <p className="text-sm font-medium text-gray-400 font-['Inter']">
                      Get clear answers to your questions so you can move forward with confidence.
                    </p>
                  </div>

                  <div className="space-y-0">
                    {/* FAQ Item 1 */}
                    <div className="border-b border-neutral-200 overflow-hidden">
                      <div
                        onClick={() => toggleFaqItem(1)}
                        className="w-full flex items-center justify-between py-4 text-left transition-colors duration-200 cursor-pointer focus:outline-none"
                      >
                        <div className="text-sm font-medium text-neutral-950 flex-1 pr-4 font-['Inter']">
                          1. Is Icegram Express suitable for beginners?
                        </div>
                        <svg
                          className={`w-4 h-4 text-gray-500 transition-transform duration-300 ease-in-out flex-shrink-0 ${openFaqItems.includes(1) ? 'rotate-180' : ''}`}
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                        </svg>
                      </div>
                      <div 
                        className={`transition-all duration-300 ease-in-out overflow-hidden ${
                          openFaqItems.includes(1) ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0'
                        }`}
                      >
                        <div className="pb-4 px-0">
                          <p className="text-sm text-neutral-500 font-['Inter'] leading-relaxed">
                            Absolutely! With an intuitive setup and a drag-and-drop editor, even non-tech users can create professional campaigns in minutes.
                          </p>
                        </div>
                      </div>
                    </div>

                    {/* FAQ Item 2 */}
                    <div className="border-b border-neutral-200 overflow-hidden">
                      <div
                        onClick={() => toggleFaqItem(2)}
                        className="w-full flex items-center justify-between py-4 text-left transition-colors duration-200 cursor-pointer"
                      >
                        <div className="text-sm font-medium text-neutral-950 flex-1 pr-4 font-['Inter']">
                          2. Why should I choose Icegram Express, and not other email marketing solutions?
                        </div>
                        <svg
                          className={`w-4 h-4 text-gray-500 transition-transform duration-300 ease-in-out flex-shrink-0 ${openFaqItems.includes(2) ? 'rotate-180' : ''}`}
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                        </svg>
                      </div>
                      <div 
                        className={`transition-all duration-300 ease-in-out overflow-hidden ${
                          openFaqItems.includes(2) ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0'
                        }`}
                      >
                        <div className="pb-4 px-0">
                          <p className="text-sm text-neutral-500 font-['Inter'] leading-relaxed">
                            If you want rock solid features, reliability and a product designed with your goals in mind, choose Icegram Express. If you want to spend a lot of money and confuse yourself, you can go with big name mail monkeys.
                          </p>
                        </div>
                      </div>
                    </div>

                    {/* FAQ Item 3 */}
                    <div className="border-b border-neutral-200 overflow-hidden">
                      <div
                        onClick={() => toggleFaqItem(3)}
                        className="w-full flex items-center justify-between py-4 text-left transition-colors duration-200 cursor-pointer"
                      >
                        <div className="text-sm font-medium text-neutral-950 flex-1 pr-4 font-['Inter']">
                          3. How does Icegram Express integrate with WooCommerce?
                        </div>
                        <svg
                          className={`w-4 h-4 text-gray-500 transition-transform duration-300 ease-in-out flex-shrink-0 ${openFaqItems.includes(3) ? 'rotate-180' : ''}`}
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                        </svg>
                      </div>
                      <div 
                        className={`transition-all duration-300 ease-in-out overflow-hidden ${
                          openFaqItems.includes(3) ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0'
                        }`}
                      >
                        <div className="pb-4 px-0">
                          <p className="text-sm text-neutral-500 font-['Inter'] leading-relaxed">
                            It offers robust WooCommerce email marketing features, including automated order notifications, cart recovery, and personalized product promotions.
                          </p>
                        </div>
                      </div>
                    </div>

                    {/* FAQ Item 4 */}
                    <div className="border-b border-neutral-200 overflow-hidden">
                      <div
                        onClick={() => toggleFaqItem(4)}
                        className="w-full flex items-center justify-between py-4 text-left transition-colors duration-200 cursor-pointer"
                      >
                        <div className="text-sm font-medium text-neutral-950 flex-1 pr-4 font-['Inter']">
                          4. What's the difference between the free and Max version?
                        </div>
                        <svg
                          className={`w-4 h-4 text-gray-500 transition-transform duration-300 ease-in-out flex-shrink-0 ${openFaqItems.includes(4) ? 'rotate-180' : ''}`}
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                        </svg>
                      </div>
                      <div 
                        className={`transition-all duration-300 ease-in-out overflow-hidden ${
                          openFaqItems.includes(4) ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0'
                        }`}
                      >
                        <div className="pb-4 px-0">
                          <p className="text-sm text-neutral-500 font-['Inter'] leading-relaxed">
                            The free version provides complete email marketing essentials, while the Pro version unlocks advanced automation, dynamic content personalization, enhanced reporting, additional templates, and much more for power users.
                          </p>
                        </div>
                      </div>
                    </div>

                    {/* FAQ Item 5 */}
                    <div className="border-b border-neutral-200 overflow-hidden">
                      <div
                        onClick={() => toggleFaqItem(5)}
                        className="w-full flex items-center justify-between py-4 text-left transition-colors duration-200 cursor-pointer"
                      >
                        <div className="text-sm font-medium text-neutral-950 flex-1 pr-4 font-['Inter']">
                          5. Can I send unlimited emails with Icegram Express?
                        </div>
                        <svg
                          className={`w-4 h-4 text-gray-500 transition-transform duration-300 ease-in-out flex-shrink-0 ${openFaqItems.includes(5) ? 'rotate-180' : ''}`}
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                        </svg>
                      </div>
                      <div 
                        className={`transition-all duration-300 ease-in-out overflow-hidden ${
                          openFaqItems.includes(5) ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0'
                        }`}
                      >
                        <div className="pb-4 px-0">
                          <p className="text-sm text-neutral-500 font-['Inter'] leading-relaxed">
                            Yes. Icegram Express itself doesn't limit how many emails you send. However, the actual sending depends on your hosting server or the email delivery service (SMTP, Icegram Mailer, etc.) you connect with.
                          </p>
                        </div>
                      </div>
                    </div>

                    {/* FAQ Item 6 */}
                    <div className="border-b border-neutral-200 overflow-hidden">
                      <div
                        onClick={() => toggleFaqItem(6)}
                        className="w-full flex items-center justify-between py-4 text-left transition-colors duration-200 cursor-pointer"
                      >
                        <div className="text-sm font-medium text-neutral-950 flex-1 pr-4 font-['Inter']">
                          6. How do I add an unsubscribe link in my emails?
                        </div>
                        <svg
                          className={`w-4 h-4 text-gray-500 transition-transform duration-300 ease-in-out flex-shrink-0 ${openFaqItems.includes(6) ? 'rotate-180' : ''}`}
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                        </svg>
                      </div>
                      <div 
                        className={`transition-all duration-300 ease-in-out overflow-hidden ${
                          openFaqItems.includes(6) ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0'
                        }`}
                      >
                        <div className="pb-4 px-0">
                          <p className="text-sm text-neutral-500 font-['Inter'] leading-relaxed">
                            Every email should have an unsubscribe link. In Icegram Express, just use the tag <code className="bg-gray-100 px-1 py-0.5 rounded text-xs font-mono">{`{{subscriber.unsubscribe_link}}`}</code> in your email body or footer, and it will automatically add the correct unsubscribe link for each subscriber.
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Popular Content Section - Commented out as we are not releasing this now */}
        <div className="flex flex-col gap-3 w-full" data-node-id="49:11042">
          <div 
            className="font-['Inter'] font-semibold text-[18px] text-neutral-950 leading-[28px]"
            data-node-id="49:11043"
          >
            Popular Content
          </div>
          
          <div className="flex flex-col gap-4 items-center w-full" data-node-id="49:11044">
            <div className="flex items-center justify-between w-full" data-node-id="49:11045">
              {/* Card 1 - Mastering Lead Generation */}
              <a 
                href="https://www.icegram.com/email-delivery-mistakes/"
                target="_blank"
                rel="noopener noreferrer"
                className="bg-[#ffffff] rounded-[14px] w-[300px] shrink-0 border border-neutral-200 shadow-sm hover:shadow-lg hover:border-violet-300 transition-all duration-200 cursor-pointer text-decoration-none"
                data-node-id="52:19957"
              >
                <div 
                  className="bg-[#ffffff] rounded-[14px] w-[300px] shrink-0 border border-neutral-200 shadow-sm"
                  data-node-id="52:19937"
                >
                  <div className="flex flex-col overflow-hidden w-[300px]">
                    <div className="flex flex-col gap-2">
                      <div 
                        className="w-full aspect-[240/135] bg-center bg-cover rounded-t-[14px]"
                        style={{ backgroundImage: `url(${images.emailDeliveryMistakes}), url(${images.emailDeliveryMistakes})` }}
                      />
                    </div>
                    <div className="flex gap-2 items-start pb-0 pt-3 px-3">
                      <div className="flex flex-col gap-1.5 grow">
                        <div className="font-['Inter'] font-semibold text-[16px] text-neutral-950 leading-[24px] line-clamp-2">
                          Top Six Email Delivery Mistakes WordPress Users Make
                        </div>
                      </div>
                    </div>
                    <div className="flex flex-col gap-2 p-[12px]">
                      <div className="flex items-center justify-between w-full">
                        <div className="font-['Inter'] font-normal text-[12px] text-neutral-500 leading-[16px]">
                          Team Icegram
                        </div>
                        <div className="flex gap-2 items-center">
                          <div className="bg-[#ffffff] flex gap-1 items-center justify-center px-2 py-0.5 rounded-lg">
                            <div className="font-['Inter'] font-normal text-[12px] text-neutral-500 leading-[16px]">
                              2 MINS READ
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </a>

              {/* Card 2 - Boost Engagement with Popups */}
              <a 
                href="https://www.icegram.com/monthly-newsletter/"
                target="_blank"
                rel="noopener noreferrer"
                className="bg-[#ffffff] rounded-[14px] w-[300px] shrink-0 border border-neutral-200 shadow-sm hover:shadow-lg hover:border-violet-300 transition-all duration-200 cursor-pointer text-decoration-none"
                data-node-id="52:19957"
              >
                <div 
                  className="bg-[#ffffff] rounded-[14px] w-[300px] shrink-0 border border-neutral-200 shadow-sm"
                  data-node-id="52:19957"
                >
                  <div className="flex flex-col overflow-hidden w-[300px]">
                    <div className="flex flex-col gap-2">
                      <div 
                        className="aspect-[240/135] bg-center bg-cover bg-no-repeat w-full rounded-t-[14px]"
                        style={{ backgroundImage: `url(${images.monthlyNewsletter}), url(${images.monthlyNewsletter})` }}
                      />
                    </div>
                    <div className="flex gap-2 items-start pb-0 pt-3 px-3">
                      <div className="flex flex-col gap-1.5 grow">
                        <div className="font-['Inter'] font-semibold text-[16px] text-neutral-950 leading-[24px] line-clamp-2">
                          Monthly Newsletter: Best Ideas To Inspire You
                        </div>
                      </div>
                    </div>
                    <div className="flex flex-col gap-2 p-[12px]">
                      <div className="flex items-center justify-between w-full">
                        <div className="font-['Inter'] font-normal text-[12px] text-neutral-500 leading-[16px]">
                          Team Icegram
                        </div>
                        <div className="flex gap-2 items-center">
                          <div className="bg-[#ffffff] flex gap-1 items-center justify-center px-2 py-0.5 rounded-lg">
                            <div className="font-['Inter'] font-normal text-[12px] text-neutral-500 leading-[16px]">
                              5 MINS READ
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </a>

              {/* Card 3 - Boost Conversions with Icegram */}
              <a 
                href="https://www.icegram.com/mailer-for-email-delivery/"
                target="_blank"
                rel="noopener noreferrer"
                className="bg-[#ffffff] rounded-[14px] w-[300px] shrink-0 border border-neutral-200 shadow-sm hover:shadow-lg hover:border-violet-300 transition-all duration-200 cursor-pointer text-decoration-none"
                data-node-id="52:19957"
              >
                <div 
                  className="bg-[#ffffff] rounded-[14px] w-[300px] shrink-0 border border-neutral-200 shadow-sm"
                  data-node-id="52:19917"
                >
                  <div className="flex flex-col overflow-hidden w-[300px]">
                    <div className="flex flex-col gap-2">
                      <div 
                        className="aspect-[240/135] bg-center bg-cover bg-no-repeat w-full rounded-t-[14px]"
                        style={{ backgroundImage: `url(${images.reliableWordpressEmailDelivery}), url(${images.reliableWordpressEmailDelivery})` }}
                      />
                    </div>
                    <div className="flex gap-2 items-start pb-0 pt-3 px-3">
                      <div className="flex flex-col gap-1.5 grow">
                        <div className="font-['Inter'] font-semibold text-[16px] text-neutral-950 leading-[24px] line-clamp-2">
                          How to Set Up Icegram Mailer for Reliable Email Delivery in WordPress?
                        </div>
                      </div>
                    </div>
                    <div className="flex flex-col gap-2 p-[12px]">
                      <div className="flex items-center justify-between w-full">
                        <div className="font-['Inter'] font-normal text-[12px] text-neutral-500 leading-[16px]">
                          Team Icegram
                        </div>
                        <div className="flex gap-2 items-center">
                          <div className="bg-[#ffffff] flex gap-1 items-center justify-center px-2 py-0.5 rounded-lg">
                            <div className="font-['Inter'] font-normal text-[12px] text-neutral-500 leading-[16px]">
                              8 MINS READ
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </a>

              {/* Card 4 - Email Marketing Automation 101 */}
              <a 
                href="https://www.icegram.com/welcome-email-templates-for-new-subscribers/"
                target="_blank"
                rel="noopener noreferrer"
                className="bg-[#ffffff] rounded-[14px] w-[300px] shrink-0 border border-neutral-200 shadow-sm hover:shadow-lg hover:border-violet-300 transition-all duration-200 cursor-pointer text-decoration-none mr-2"
                data-node-id="52:19957"
              >
                <div 
                  className="bg-[#ffffff] rounded-[14px] w-[300px] shrink-0 border border-neutral-200 shadow-sm"
                  data-node-id="52:19977"
                >
                  <div className="flex flex-col overflow-hidden w-[300px]">
                    <div className="flex flex-col gap-2">
                      <div 
                        className="aspect-[240/135] bg-center bg-cover bg-no-repeat w-full rounded-t-[14px]"
                        style={{ backgroundImage: `url(${images.emailTemplateForNewSubscriber}), url(${images.emailTemplateForNewSubscriber})` }}
                      />
                    </div>
                    <div className="flex gap-2 items-start pb-0 pt-3 px-3">
                      <div className="flex flex-col gap-1.5 grow">
                        <div className="font-['Inter'] font-semibold text-[16px] text-neutral-950 leading-[24px] line-clamp-2">
                          High Converting Welcome Email Templates for New Subscribers
                        </div>
                      </div>
                    </div>
                    <div className="flex flex-col gap-2 p-[12px]">
                      <div className="flex items-center justify-between w-full">
                        <div className="font-['Inter'] font-normal text-[12px] text-neutral-500 leading-[16px]">
                          Team Icegram
                        </div>
                        <div className="flex gap-2 items-center">
                          <div className="bg-[#ffffff] flex gap-1 items-center justify-center px-2 py-0.5 rounded-lg">
                            <div className="font-['Inter'] font-normal text-[12px] text-neutral-500 leading-[16px]">
                              7 MINS READ
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </a>
            </div>
            {/* View All Button */}
            <a href="https://www.icegram.com/blog/" target="_blank">
              <div 
                className="flex gap-1 items-center justify-center px-2.5 py-1.5 rounded-lg cursor-pointer transition-colors"
                data-node-id="49:11130"
              >
                <div className="font-['Inter'] font-medium text-[14px] text-neutral-900 leading-[20px]">
                  View All
                </div>
                <div className="size-5">
                  <img alt="" className="w-5 h-5" src={images.popularContentViewAllIcon} />
                </div>
              </div>
            </a>
          </div>
        </div>

        {/* Try Our Other Products Section */}
        <div className="flex flex-col gap-3 w-full">
          <h2 className="text-lg font-semibold text-neutral-950">Try our other products</h2>
          
          <div className="flex gap-4">
            {(() => {
              // Define the products with their details - Updated slugs to match API
              const products = [
                {
                  slug: "icegram-mailer", // Matches "icegram-mailer/icegram-mailer.php"
                  name: "Icegram Mailer",
                  description: "Reliable email delivery service with advanced analytics and automation.",
                  icon: images.icegramExpressIcon,
                  alt: "Icegram Mailer"
                },
                {
                  slug: "icegram", // Matches "icegram/icegram.php"
                  name: "Icegram Engage",
                  description: "Drive traffic, engage readers, and grow revenue effortlessly.",
                  icon: images.icegramEngageIcon,
                  alt: "Icegram Engage"
                },
                {
                  slug: "icegram-rainmaker", // Matches "icegram-rainmaker/icegram-rainmaker.php"
                  name: "Icegram Collect", 
                  description: "Create contact forms and subscription widgets in seconds.",
                  icon: images.icegramCollectIcon,
                  alt: "Icegram Collect"
                }
              ];

              return products.map((product) => {
                // Find plugin status from dashboard data
                const pluginStatus = dashboardData?.icegram_plugins?.find(
                  plugin => plugin.slug.split('/')[0] === product.slug || plugin.name === product.name
                );

                // Get button info based on plugin status
                const buttonInfo = pluginStatus 
                  ? getPluginButtonInfo(pluginStatus)
                  : { text: "Install", url: `${getBaseUrl()}/wp-admin/plugin-install.php?s=icegram&tab=search&type=term`, variant: "install" as const };

                // Handle click event for Learn more button
                const handleLearnMoreClick = () => {
                  window.open(buttonInfo.url, '_blank', 'noopener,noreferrer');
                };

                return (
                  <div key={product.slug} className="bg-white relative rounded-[12px] flex-1" data-name="navigation menu content item">
                    <div className="box-border content-stretch flex flex-col gap-6 items-start justify-center overflow-clip p-[16px] relative size-full">
                      <div className="content-stretch flex gap-2 items-center justify-between relative shrink-0 w-full">
                        <div className="content-stretch flex flex-col font-['Inter'] font-medium gap-2 items-start justify-start leading-[0] not-italic relative shrink-0 w-[281.333px]">
                          <div className="relative shrink-0">
                            <p className="leading-[24px] whitespace-pre text-[16px] text-neutral-950 text-nowrap">{product.name}</p>
                          </div>
                          <div className="min-w-full relative shrink-0" style={{ width: "min-content" }}>
                            <p className="leading-[20px] text-[14px] text-neutral-500">{product.description}</p>
                          </div>
                        </div>
                        <div className="box-border content-stretch flex gap-2.5 items-center justify-center p-[25px] relative rounded-[12px] shrink-0 size-[74px]" style={{background: "linear-gradient(132deg, rgba(94, 25, 207, 0.24) -3.22%, rgba(208, 179, 255, 0.24) 126.61%)"}}>
                          <div className="relative shrink-0 size-9">
                            <img alt={product.alt} className="block max-w-none size-full" src={product.icon} />
                          </div>
                        </div>
                      </div>
                      <div onClick={handleLearnMoreClick} className="box-border content-stretch flex gap-2 h-9 items-center justify-center px-4 py-2 relative rounded-[8px] shrink-0 cursor-pointer transition-colors duration-200" data-name="Button">
                        <div aria-hidden="true" className="absolute border border-[#5e19cf] border-solid inset-0 pointer-events-none rounded-[8px] shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)]" />
                        <div className="flex flex-col font-['Inter'] font-medium justify-center leading-[0] not-italic relative shrink-0 text-[#5e19cf] text-[14px] text-nowrap">
                          <p className="leading-[20px] whitespace-pre">Learn more</p>
                        </div>
                      </div>
                    </div>
                    <div aria-hidden="true" className="absolute border border-[rgba(163,163,163,0.1)] border-solid inset-0 pointer-events-none rounded-[12px]" />
                  </div>
                );
              });
            })()}
          </div>
        </div>

        {/* Made in India Section */}
        <div className="flex justify-start items-center py-3">
          <h2 
            className="font-semibold"
            style={{
              color: 'var(--tailwind-colors-gray-300, #D1D5DB)',
              fontFamily: 'Inter',
              fontSize: '30px',
              fontStyle: 'normal',
              fontWeight: '600',
              lineHeight: '36px',
              letterSpacing: '-0.225px'
            }}
          >
            Made in India with{" "}
            <span 
              style={{
                color: 'var(--tailwind-colors-red-500, #EF4444)',
                fontFamily: 'Inter',
                fontSize: '36px',
                fontStyle: 'normal',
                fontWeight: '600',
                lineHeight: '48px',
                letterSpacing: '-0.27px'
              }}
            >
              ❤️
            </span>
          </h2>
        </div>
      </div>

      {/* Icegram Mailer Promotion Popup */}
      {showIcegramMailerPopup && (
        <div id="ig-es-icegram-mailer-promotion-popup" className="form-fields ig-es-popup-container fixed inset-0 z-[999999] flex items-center justify-center">
          <div 
            className="ig-es-popup-overlay fixed inset-0 bg-black bg-opacity-50"
            onClick={() => setShowIcegramMailerPopup(false)}
          ></div>
          <div className="ig-es-popup bg-white rounded-[10px] shadow-[0px_10px_15px_-3px_rgba(0,0,0,0.1),0px_4px_6px_-4px_rgba(0,0,0,0.1)] w-[525px] max-w-[90vw] relative z-10">
            {/* Close button */}
            <div className="absolute top-4 right-4 z-10">
              <button 
                onClick={() => setShowIcegramMailerPopup(false)} 
                className="cross p-1 hover:bg-gray-100 rounded-full transition-colors"
              >
                <svg className="h-5 w-5" width="20" height="20" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M5.04366 4.17217L7.49984 6.62835L9.94329 4.1849C9.99726 4.12745 10.0623 4.08149 10.1344 4.04978C10.2066 4.01807 10.2844 4.00127 10.3633 4.00037C10.532 4.00037 10.6939 4.06741 10.8132 4.18674C10.9325 4.30607 10.9996 4.46792 10.9996 4.63668C11.0011 4.71469 10.9866 4.79219 10.957 4.86441C10.9275 4.93663 10.8835 5.00204 10.8278 5.05665L8.3525 7.5001L10.8278 9.97537C10.9326 10.078 10.9941 10.2169 10.9996 10.3635C10.9996 10.5323 10.9325 10.6941 10.8132 10.8135C10.6939 10.9328 10.532 10.9998 10.3633 10.9998C10.2822 11.0032 10.2013 10.9897 10.1257 10.9601C10.0501 10.9305 9.98147 10.8855 9.9242 10.828L7.49984 8.37185L5.05002 10.8217C4.99626 10.8772 4.93203 10.9215 4.86104 10.9521C4.79005 10.9827 4.71371 10.9989 4.63642 10.9998C4.46766 10.9998 4.30581 10.9328 4.18648 10.8135C4.06714 10.6941 4.0001 10.5323 4.0001 10.3635C3.99862 10.2855 4.01309 10.208 4.04264 10.1358C4.07218 10.0636 4.11617 9.99816 4.17191 9.94355L6.64718 7.5001L4.17191 5.02483C4.06703 4.92223 4.00554 4.7833 4.0001 4.63668C4.0001 4.46792 4.06714 4.30607 4.18648 4.18674C4.30581 4.06741 4.46766 4.00037 4.63642 4.00037C4.78913 4.00228 4.93549 4.064 5.04366 4.17217Z" fill="#575362"></path>
                </svg>
              </button>
            </div>

            <div className="p-6 flex flex-col gap-6 items-center">
              {/* Header Section */}
              <div className="flex flex-col gap-1.5 items-start w-full">
                <div className="w-full">
                  <p className="leading-[28px] font-['Inter'] font-semibold text-[18px] text-neutral-950 ">
                    <span id="popup-header-text">Supercharge your emails with our Icegram Mailer plugin!</span>
                  </p>
                </div>
                <div className="w-full">
                  <p className="leading-[20px] font-['Inter'] font-normal text-[14px] text-neutral-500">
                    <span id="popup-subtitle-text">Get started with 200 free emails per month and enjoy reliable delivery.</span>
                  </p>
                </div>
              </div>

              {/* Step 1 - Benefits */}
              <div id="sending-service-benefits" className="flex flex-col gap-2 items-start w-full">
                {/* Feature List Items */}
                <div className="flex gap-1 items-center w-full">
                  <div className="size-4 flex items-center justify-center">
                    <svg className="w-4 h-4" viewBox="0 0 16 16" fill="none">
                      <path d="M13.3333 4L6 11.3333L2.66667 8" stroke="#5e19cf" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                  </div>
                    <p className="leading-[20px] font-['Inter'] font-medium text-[14px] text-neutral-950">Start with 200 free emails / month</p>
                </div>

                <div className="flex gap-1 items-center w-full">
                  <div className="size-4 flex items-center justify-center">
                    <svg className="w-4 h-4" viewBox="0 0 16 16" fill="none">
                      <path d="M13.3333 4L6 11.3333L2.66667 8" stroke="#5e19cf" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                  </div>
                    <p className="leading-[20px] font-['Inter'] font-medium text-[14px] text-neutral-950">High speed email sending</p>
                </div>

                <div className="flex gap-1 items-center w-full">
                  <div className="size-4 flex items-center justify-center">
                    <svg className="w-4 h-4" viewBox="0 0 16 16" fill="none">
                      <path d="M13.3333 4L6 11.3333L2.66667 8" stroke="#5e19cf" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                  </div>
                    <p className="leading-[20px] font-['Inter'] font-medium text-[14px] text-neutral-950">Reliable email delivery</p>
                </div>
              </div>

              {/* Step 2 - Progress Tasks */}
              <div id="sending-service-onboarding-tasks-list" className="flex-col gap-2 items-start w-full" style={{display: 'none'}}>
                {/* Dynamic Task Items - These will be managed by JavaScript */}
                <div id="ig-es-onboard-install_mailer_plugin" className="flex gap-1 items-center w-full mb-2" data-status="">
                  <div className="size-4 flex items-center justify-center">
                    <div className="bg-gray-100 rounded-full size-3"></div>
                  </div>
                  <p className="font-['Inter'] font-medium text-[14px] text-neutral-950 leading-[20px]">Installing...</p>
                </div>

                <div id="ig-es-onboard-activate_mailer_plugin" className="flex gap-1 items-center w-full mb-2" data-status="">
                  <div className="size-4 flex items-center justify-center">
                    <div className="bg-gray-100 rounded-full size-3"></div>
                  </div>
                  <p className="font-['Inter'] font-medium text-[14px] text-neutral-950 leading-[20px]">Activating...</p>
                </div>

                <div id="ig-es-onboard-redirect_to_mailer_plugin_dashboard" className="flex gap-1 items-center w-full mb-2" data-status="">
                  <div className="relative flex items-center justify-center flex-shrink-0 w-4 h-4">
                    <div className="bg-gray-100 rounded-full size-3"></div>
                  </div>
                  <p className="font-['Inter'] font-medium text-[14px] text-neutral-950 leading-[20px]">Redirecting...</p>
                </div>

                {/* Icegram Logo and Trust message - Only in Step 2 */}
                <div className="flex flex-col gap-2 items-center mt-4 w-full">
                  {/* Icegram Logo */}
                  <div className="h-6 w-[85px] flex items-center justify-center">
                    <img 
                      src={`${adminData.baseUrl}/images/icegram-logo-footer.svg`} 
                      alt="Icegram"
                      className="h-full w-auto object-contain"
                      onError={(e) => {
                        // Fallback to text if image fails to load
                        e.currentTarget.style.display = 'none';
                        const textElement = document.createElement('div');
                        textElement.innerHTML = '<span style="color: #5e19cf; font-weight: 600; font-size: 18px;">icegram</span>';
                        e.currentTarget.parentNode?.appendChild(textElement);
                      }}
                    />
                  </div>
                  
                  {/* Trust message */}
                  <div className="font-['Inter'] font-normal text-[12px] text-center text-neutral-500 leading-none">
                    <p>Icegram is trusted by over 250,000 service professionals</p>
                  </div>
                </div>
              </div>

              {/* Footer Section */}
              <div id="ig-es-popup-footer-section" className="flex flex-col gap-2 items-center w-full">
                {/* CTA Button */}
                <div className="flex justify-center w-full mt-2">
                  <a id="ig-ess-optin-cta" href="#" className="inline-block" style={{display: 'block'}}>
                    <button 
                      type="button" 
                      className="bg-[#5e19cf] box-border content-stretch flex gap-2 h-9 items-center justify-center px-4 py-2 relative rounded-lg shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] shrink-0 hover:bg-[#4a0fb8] transition-colors duration-200 text-white"
                    >
                      <span className="button-text">Activate for free →</span>
                    </button>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}