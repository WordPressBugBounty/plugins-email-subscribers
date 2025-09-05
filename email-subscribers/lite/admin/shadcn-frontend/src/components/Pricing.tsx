import React, { useState } from "react";
import { useKeenSlider } from "keen-slider/react";
import "keen-slider/keen-slider.min.css";
import { couponIcon, chevronDownPurpleCustom } from "../assets/images";

export default function Pricing() {
  const adminData = (window as any).icegramExpressAdminData;

  // Plan icon assets - using new Figma icons
  const freePlanIcon = `${adminData.baseUrl}/images/plan-icon-free.svg`;
  const freePlanCurrentIcon = `${adminData.baseUrl}/images/plan-current-icon.svg`;
  const proPlanIcon = `${adminData.baseUrl}/images/plan-icon-pro-new.svg`;
  const maxPlanIcon = `${adminData.baseUrl}/images/plan-icon-max.svg`;

  // FAQ collapse state
  const [openFaqIdx, setOpenFaqIdx] = useState<number | null>(0);
  // Keen-slider setup (must be inside function component)
  const [currentSlide, setCurrentSlide] = useState(0);
  const [sliderRef, instanceRef] = useKeenSlider({
    slides: { perView: 2.5, spacing: 32 },
    loop: true,
    slideChanged(slider) {
      setCurrentSlide(slider.track.details.rel);
    },
  });
  // Set page background color
  if (typeof document !== 'undefined') {
    document.body.style.background = '#F6F5F8';
  }
  const [showAll, setShowAll] = useState(false);

  // Figma asset constants - using proper pricing assets
  const pricingInfoIcon = `${adminData.baseUrl}/images/pricing-info-icon.svg`;
  const pricingCrossIcon = `${adminData.baseUrl}/images/pricing-cross-icon.svg`;
  const checkmarkLargeIcon = `${adminData.baseUrl}/images/checkmark-large.svg`;

  // Features grouped by section
  const crossIcon = <img src={pricingCrossIcon} alt="Unavailable" className="w-5 h-5 inline-block align-middle" />;
  const checkmarkLarge = <img src={checkmarkLargeIcon} alt="Included" className="w-6 h-6 inline-block align-middle" />;
  
  const emailManagementFeatures = [
    {
      name: "3rd Party SMTP Configuration",
      free: "Pepipost",
      pro: "Default SMTP",
      max: "Amazon SES, Mailgun, SendGrid, SparkPost, Postmark, Sendinblue, Mailjet & Mailersend.",
      icon: pricingInfoIcon,
      tooltipMsg: "Connect With SMTP Services To Reliable Send Transactional Emails. Also Supports Automatic Bounce Handling.",
    },
    {
      name: "Detailed Reports/Analytics",
      free: "Overall Summary",
      pro: "Overall Summary",
      max: "Detailed Report",
      icon: pricingInfoIcon,
      tooltipMsg: "Get comprehensive analytics and detailed reports about your email campaigns performance and engagement metrics.",
    },
    {
      name: "Weekly Summary Email",
      free: "Basic Summary",
      pro: "Basic Summary",
      max: "Advanced Summary",
      icon: pricingInfoIcon,
      tooltipMsg: "Receive automated weekly summary emails with key metrics and insights about your email campaigns.",
    },
    {
      name: "Drag and Drop Campaign Editor",
      free: "Basic Blocks",
      pro: "Advanced Blocks",
      max: "Advanced Blocks",
      icon: pricingInfoIcon,
      tooltipMsg: "Create beautiful email campaigns with our intuitive drag and drop editor featuring various content blocks.",
    },
    {
      name: "Automatic Batch Sending",
      free: crossIcon,
      pro: checkmarkLarge,
      max: checkmarkLarge,
      icon: pricingInfoIcon,
      tooltipMsg: "Automatically send emails in batches to improve deliverability and avoid spam filters.",
    },
    {
      name: "Captcha & Security",
      free: crossIcon,
      pro: checkmarkLarge,
      max: checkmarkLarge,
      icon: pricingInfoIcon,
      tooltipMsg: "Enhanced security features including captcha protection to prevent spam and unauthorized access.",
    },
    // Collapsed features
    { 
      name: "List Unsubscribe", 
      free: crossIcon, 
      pro: checkmarkLarge, 
      max: checkmarkLarge, 
      icon: pricingInfoIcon,
      tooltipMsg: "Automated unsubscribe handling and list management to maintain clean subscriber lists.",
    },
    { 
      name: "Comment Optin", 
      free: crossIcon, 
      pro: checkmarkLarge, 
      max: checkmarkLarge, 
      icon: pricingInfoIcon,
      tooltipMsg: "Allow users to subscribe through comment opt-in forms on your website posts.",
    },
    { 
      name: "Send WooCommerce Coupons", 
      free: crossIcon, 
      pro: crossIcon, 
      max: checkmarkLarge, 
      icon: pricingInfoIcon,
      tooltipMsg: "Integrate with WooCommerce to send discount coupons and promotional offers to subscribers.",
    },
    { 
      name: "Abandoned Cart Recovery Email", 
      free: crossIcon, 
      pro: crossIcon, 
      max: checkmarkLarge, 
      icon: pricingInfoIcon,
      tooltipMsg: "Automatically send recovery emails to customers who abandon their shopping carts.",
    },
    { 
      name: "Post Digest Notifications", 
      free: crossIcon, 
      pro: crossIcon, 
      max: checkmarkLarge, 
      icon: pricingInfoIcon,
      tooltipMsg: "Send automated digest emails with your latest blog posts and content updates.",
    },
    { 
      name: "Email Newsletter Archive", 
      free: crossIcon, 
      pro: crossIcon, 
      max: checkmarkLarge, 
      icon: pricingInfoIcon,
      tooltipMsg: "Create and maintain an archive of all your sent newsletters for easy reference.",
    },
    { 
      name: "Resend Confirmation Email", 
      free: crossIcon, 
      pro: crossIcon, 
      max: checkmarkLarge, 
      icon: pricingInfoIcon,
      tooltipMsg: "Automatically resend confirmation emails to subscribers who haven't confirmed their subscription.",
    },
    { 
      name: "Custom Contact Fields", 
      free: crossIcon, 
      pro: crossIcon, 
      max: checkmarkLarge, 
      icon: pricingInfoIcon,
      tooltipMsg: "Create custom fields to collect additional subscriber information and segment your audience.",
    },
    { 
      name: "Autoresponder & Workflows", 
      free: crossIcon, 
      pro: crossIcon, 
      max: checkmarkLarge, 
      icon: pricingInfoIcon,
      tooltipMsg: "Set up automated email sequences and workflows based on subscriber actions and triggers.",
    },
  ];

  const integrationsFeatures = [
    { 
      name: "Gmail API", 
      free: crossIcon, 
      pro: checkmarkLarge, 
      max: checkmarkLarge, 
      icon: pricingInfoIcon,
      tooltipMsg: "Integrate with Gmail API for enhanced email sending capabilities and better deliverability.",
    },
    { 
      name: "Automatic List Cleanup", 
      free: crossIcon, 
      pro: crossIcon, 
      max: checkmarkLarge, 
      icon: pricingInfoIcon,
      tooltipMsg: "Automatically clean and maintain your subscriber lists by removing invalid and bounced email addresses.",
    },
    { 
      name: "Membership Plugin Integration", 
      free: crossIcon, 
      pro: crossIcon, 
      max: "Integrate with WooCommerce Memberships, MemberPress, Paid Memberships Pro, Ultimate Members.", 
      icon: pricingInfoIcon,
      tooltipMsg: "Seamlessly integrate with popular membership plugins to sync subscriber data and automate communications.",
    },
    { 
      name: "Popular Integrations", 
      free: crossIcon, 
      pro: crossIcon, 
      max: "Integrate with WooCommerce Abandoned Cart, Easy Digital Downloads, GiveWP Donation, Yith Wishlist Item On Sale, LearnDash, Contact Form 7, Ninja Forms, Forminator, Gravity Forms & WP Forms", 
      icon: pricingInfoIcon,
      tooltipMsg: "Connect with popular WordPress plugins and third-party services to enhance your email marketing capabilities.",
    },
  ];

  const supportFeatures = [
    { 
      name: "Support", 
      free: "WordPress Forum Support", 
      pro: "Premium Support (Email)", 
      max: "VIP Support (Email + Facebook)", 
      icon: pricingInfoIcon,
      tooltipMsg: "Get dedicated support based on your plan level, from community forums to VIP priority support.",
    },
  ];
  return (
  <div className="w-full min-h-screen pl-6 pr-6 py-7 flex flex-col gap-7" data-name="Pricing" data-node-id="558:76729">
      {/* Pricing Table Section - Feature Comparison */}
      <div className="w-full pb-8 flex flex-col items-center" style={{ backgroundColor: '#f6f5f8' }}>
        {/* Header - Left aligned as per Figma design */}
        <div className="w-full pb-5">
          <div className="flex flex-col gap-1 items-start justify-start w-full">
            <p className="font-['Inter'] font-semibold text-black text-[24px] leading-[32px] tracking-[-0.144px] w-full">Upgrade your plan</p>
            <p className="font-['Inter'] font-medium text-slate-400 text-[14px] leading-[24px] w-full">Unlock premium features and take your experience to the next level</p>
          </div>
        </div>

        {/* Alert Message */}
        <div className="w-full pb-6" >
          <div className="bg-emerald-100 border border-green-300 rounded-xl px-6 py-2 flex gap-6 items-center justify-start">
            <div className="flex items-center justify-center relative shrink-0" style={{ width: '60px', height: '60px' }}>
              <img 
                src={couponIcon} 
                alt="Coupon" 
                className="block"
                style={{ width: '60px', height: '48px' }}
              />
            </div>
            <div className="flex flex-col gap-2 items-start justify-center">
              <div className="font-medium text-black">
                <p className="text-nowrap whitespace-pre">
                  <span className="leading-[20px] text-[14px]">Congratulations you have unlocked </span>
                  <span className="font-semibold leading-[24px] text-[16px]">25% off</span>
                  <span className="leading-[20px] text-[14px]"> on Icegram Express Premium plans</span>
                </p>
              </div>
              <p className="leading-[20px] text-nowrap whitespace-pre font-normal text-zinc-600 text-[14px]">Redeem now to enjoy premium features, limited time offer!</p>
            </div>
          </div>
        </div>
        <div className="grid grid-cols-4 rounded-xl border border-slate-200 border-b-0 relative">
          {/* Header Row - Figma style */}
          <div className="bg-slate-50 px-6 py-5 flex items-start justify-start h-[368px] rounded-tl-xl">
            <p className="font-['Inter'] font-semibold text-zinc-800 text-[24px] leading-[32px] tracking-tight">Plans</p>
          </div>
          {/* Free Plan */}
          <div className="bg-white px-6 py-5 flex flex-col items-start justify-between h-[368px]">
            <div className="flex flex-col gap-2 w-full">
              <div 
                className="flex items-center justify-start p-[12px] rounded-xl mb-2 w-fit"
                style={{
                  borderRadius: '12px',
                  background: 'linear-gradient(132deg, rgba(94, 25, 207, 0.24) -3.22%, rgba(208, 179, 255, 0.24) 126.61%)'
                }}
              >
                <img src={freePlanIcon} alt="Free" className="w-6 h-6" />
              </div>
              <p className="font-semibold text-zinc-800 text-[24px] leading-[32px] font-['Inter']">Free</p>
              <p className="font-normal text-zinc-600 text-[14px] leading-[24px]">Unlimited contacts, emails, forms & lists. Automatic welcome emails and new post notifications.</p>
            </div>
            <div className="flex items-center gap-2 mt-2">
              <img src={freePlanCurrentIcon} alt="Current" className="w-5 h-5" />
              <span className="font-medium text-zinc-800 text-[14px] leading-[20px]">Your current plan</span>
            </div>
          </div>
          {/* Pro Plan - with black border around entire column */}
          <div className="bg-white px-6 py-5 flex flex-col items-start justify-between h-[368px] relative border-black border border-b-0">
            <div className="flex flex-col gap-2 w-full">
              <div 
                className="flex items-center justify-start p-[12px] rounded-xl mb-2 w-fit"
                style={{
                  borderRadius: '12px',
                  background: 'linear-gradient(132deg, rgba(94, 25, 207, 0.24) -3.22%, rgba(208, 179, 255, 0.24) 126.61%)'
                }}
              >
                <img src={proPlanIcon} alt="Pro" className="w-6 h-6" />
              </div>
              <div className="font-semibold text-zinc-800 text-[24px] leading-[32px] tracking-[-0.144px]">Pro</div>
              <div className="font-normal text-zinc-600 text-[14px] leading-[24px] w-[230px]">Everything in Free + Automatic batch sending, Captcha, Advanced blocks.</div>
            </div>
            <div className="flex flex-col gap-1 w-full">
              <div className="flex items-center gap-2">
                <div className="text-zinc-400 font-medium">
                  <span className="line-through text-[12px]">$</span>
                  <span className="line-through text-[20px] tracking-[-0.1px]">129</span>
                </div>
                <span className="bg-green-100 text-green-600 text-[12px] font-medium rounded-lg px-1.5 py-0.5">Save 20%</span>
              </div>
              <div>
                <span className="text-zinc-800 text-[12px]">$</span>
                <span className="font-semibold text-zinc-800 text-[24px] leading-[32px] tracking-[-0.144px]">96.75 </span>
                <span className="font-semibold text-zinc-400 text-[14px] leading-[20px]">/year</span>
              </div>
            </div>
            <a href="https://www.icegram.com/?buy-now=39043&qty=1&coupon=es-upgrade-25&page=6&with-cart=1&utm_source=ig_es&utm_medium=in_app_pricing&utm_campaign=pro_plan_cta" target="_blank" rel="noopener noreferrer" className="w-full">
              <button className="bg-[#5e19cf] text-white font-semibold text-[16px] px-4 py-2 rounded-md w-full">Upgrade to Pro</button>
            </a>
            {/* Most Popular Tag - positioned exactly as in Figma with proper z-index */}
            <div className="absolute bg-black flex items-center justify-center left-1/2 px-2 py-1.5 rounded-lg top-[-16px] translate-x-[-50%] w-[124px] z-50">
              <div className="font-medium text-white text-[12px] leading-[20px] whitespace-pre">Most Popular</div>
            </div>
          </div>
          {/* Max Plan */}
          <div className="bg-white px-6 py-5 flex flex-col items-start justify-between h-[368px] rounded-tr-xl">
            <div className="flex flex-col gap-2 w-full">
              <div 
                className="flex items-center justify-start p-[12px] rounded-xl mb-2 w-fit"
                style={{
                  borderRadius: '12px',
                  background: 'linear-gradient(132deg, rgba(94, 25, 207, 0.24) -3.22%, rgba(208, 179, 255, 0.24) 126.61%)'
                }}
              >
                <img src={maxPlanIcon} alt="Max" className="w-6 h-6" />
              </div>
              <div className="font-semibold text-zinc-800 text-[24px] leading-[32px]">Max</div>
              <div className="font-normal text-zinc-600 text-[14px] leading-[24px]">Everything in Pro + Integrations, List cleanup, Cart recovery emails, Autoresponders</div>
            </div>
            <div className="flex flex-col gap-1 w-full mt-2">
              <div className="flex items-center gap-2">
                <div className="text-zinc-400 font-medium">
                  <span className="line-through text-[12px]">$</span>
                  <span className="line-through text-[20px] tracking-[-0.1px]">229</span>
                </div>
                <span className="bg-green-100 text-green-600 text-[12px] font-medium rounded-lg px-2 py-0.5">Save 31%</span>
              </div>
              <div>
                <span className="text-zinc-800 text-[12px]">$</span>
                <span className="font-semibold text-zinc-800 text-[24px] leading-[32px]">171.75 </span>
                <span className="font-semibold text-zinc-400 text-[14px] leading-[20px]">/year</span>
              </div>
            </div>
            <a href="https://www.icegram.com/?buy-now=404335&qty=1&coupon=es-upgrade-25&page=6&with-cart=1&utm_source=ig_es&utm_medium=in_app_pricing&utm_campaign=max" target="_blank" rel="noopener noreferrer" className="w-full">
              <button className="bg-[#5e19cf] text-white font-semibold text-[16px] px-4 py-2 rounded-md w-full mt-2">Upgrade to Max</button>
            </a>
          </div>
          {/* Email Management Header Row */}
          <div className="bg-slate-50 px-4 pt-4 border-t border-slate-200 text-[#52525B] font-normal font-['Inter',_sans-serif] text-[16px] leading-[24px] flex items-center">
            Email Management
          </div>
          <div className="bg-white border-t border-slate-200"></div>
          <div className="bg-white border-t border-slate-200 relative">
            <div className="absolute inset-0 border-l border-r border-black pointer-events-none"></div>
          </div>
          <div className="bg-white border-t border-slate-200"></div>

          {/* Feature Rows - show first 6, collapse rest */}
            {emailManagementFeatures.slice(0, 6).map((feature, index) => ( 
            <React.Fragment key={feature.name}>
              {/* Add separator line after 3rd Party SMTP Configuration */}
              {index === 1 && (
                <>
                  <div className="col-span-4 border-t border-gray-300 mx-auto" style={{ width: '1202px' }}></div>
                </>
              )}
              {/* Add separator line after Detailed Reports/Analytics */}
              {index === 2 && (
                <>
                  <div className="col-span-4 border-t border-gray-300 mx-auto" style={{ width: '1202px' }}></div>
                </>
              )}
              {/* Add separator lines for Weekly Summary Email onwards */}
              {index === 3 && (
                <>
                  <div className="col-span-4 border-t border-gray-300 mx-auto" style={{ width: '1202px' }}></div>
                </>
              )}
              {/* Add separator line starting from Automatic Batch Sending (index 4) */}
              {index === 4 && (
                <>
                  <div className="col-span-4 border-t border-gray-300 mx-auto" style={{ width: '1202px' }}></div>
                </>
              )}
              {/* Add separator line for Captcha & Security (index 5) */}
              {index === 5 && (
                <>
                  <div className="col-span-4 border-t border-gray-300 mx-auto" style={{ width: '1202px' }}></div>
                </>
              )}
              <div className={`bg-slate-50 px-4 border-t-0 text-[#27272A] font-['Inter',_sans-serif] text-[16px] font-semibold leading-[24px] flex items-center ${index === 0 ? 'pt-4' : 'py-4'}`}>
                <div className="flex items-center">
                  {feature.name}
                  {feature.tooltipMsg ? (
                    <div className="relative group ml-2">
                      <img src={feature.icon} alt="" className="w-4 h-4 cursor-help" />
                      <div className="absolute bottom-full left-0 mb-2 px-4 py-3 bg-gray-800 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-50" style={{ width: '320px', fontWeight: '400', transform: 'translateX(-50%)' }}>
                        {feature.tooltipMsg}
                        <div className="absolute top-full transform -translate-x-1/2 border-4 border-transparent border-t-gray-800" style={{ left: '52%' }}></div>
                      </div>
                    </div>
                  ) : (
                    <img src={feature.icon} alt="" className="w-4 h-4 ml-2" />
                  )}
                </div>
              </div>
              <div className={`bg-white px-3 border-t-0 flex items-center justify-start font-medium ${index === 0 ? 'pt-4 text-[14px]' : (index >= 1 && index <= 3) ? 'py-4 text-[14px]' : 'py-4'}`}>{feature.free}</div>
              <div className={`bg-white px-3 border-t-0 flex items-center justify-start relative font-medium ${index === 0 ? 'pt-4 text-[14px]' : (index >= 1 && index <= 3) ? 'py-4 text-[14px]' : 'py-4'}`}>
                {feature.pro}
                {/* Add border overlay for Pro column */}
                <div className="absolute inset-0 border-l border-r border-black pointer-events-none"></div>
              </div>
              <div className={`bg-white px-3 border-t-0 flex items-center justify-start font-medium ${index === 0 ? 'pt-4 text-[14px]' : (index >= 1 && index <= 3) ? 'py-4 text-[14px]' : 'py-4'}`}>{feature.max}</div>
            </React.Fragment>
          ))}
          
          {/* Gradient Overlay - only show when not expanded to create fade effect */}
          {!showAll && (
            <div 
              className="absolute inset-x-0 pointer-events-none col-span-4 z-10"
              style={{
                background: 'linear-gradient(180deg, rgba(246, 245, 248, 0.00) 8.05%, #F6F5F8 68.79%)',
                bottom: '0',
                height: '149px'
              }}
            />
          )}
          
          {/* See All Features Text Link Row (spans all columns) - only show when not expanded */}
          {!showAll && (
            <div className="col-span-4 bg-white px-3 py-6 border-t border-slate-200 border-l-0 border-r-0 border-b-0 flex justify-center">
              <span
                className="text-[#5e19cf] font-semibold text-[16px] flex items-center gap-1 cursor-pointer select-none hover:underline"
                style={{ zIndex: 99 }}
                onClick={() => setShowAll(true)}
              >
                See All Features
                <img 
                  src={chevronDownPurpleCustom} 
                  alt="Expand" 
                  className="w-5 h-5"
                />
              </span>
            </div>
          )}
          {/* Collapsed sections: only visible when showAll is true */}
          {showAll && <>
            {/* Email Management - rest of features */}
            {emailManagementFeatures.slice(6).map((feature, index) => (
              <React.Fragment key={feature.name}>
                {/* Add separator line for each collapsed feature from start to Autoresponder & Workflows */}
                {index <= 8 && (
                  <>
                    <div className="col-span-4 border-t border-gray-300 mx-auto" style={{ width: '1202px' }}></div>
                  </>
                )}
                <div className="bg-slate-50 px-4 py-4 text-[#27272A] font-['Inter',_sans-serif] text-[16px] font-semibold leading-[24px] flex items-center">
                  <div className="flex items-center">
                    {feature.name}
                    {feature.tooltipMsg ? (
                      <div className="relative group ml-2">
                        <img src={feature.icon} alt="" className="w-4 h-4 cursor-help" />
                        <div className="absolute bottom-full left-0 mb-2 px-4 py-3 bg-gray-800 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-50" style={{ width: '320px', fontWeight: '400', transform: 'translateX(-50%)' }}>
                          {feature.tooltipMsg}
                          <div className="absolute top-full transform -translate-x-1/2 border-4 border-transparent border-t-gray-800" style={{ left: '52%' }}></div>
                        </div>
                      </div>
                    ) : (
                      <img src={feature.icon} alt="" className="w-4 h-4 ml-2" />
                    )}
                  </div>
                </div>
                <div className="bg-white px-3 py-4 flex items-start justify-start font-medium">{feature.free}</div>
                <div className="bg-white px-3 py-4 flex items-start justify-start relative font-medium">
                  {feature.pro}
                  {/* Add border overlay for Pro column */}
                  <div className="absolute inset-0 border-l border-r border-black pointer-events-none"></div>
                </div>
                <div className="bg-white px-3 py-4 flex items-start justify-start font-medium">{feature.max}</div>
              </React.Fragment>
            ))}
            {/* Integrations & APIs Header Row */}
            <div className="bg-slate-50 px-3 pt-4 text-[#52525B] font-normal font-['Inter',_sans-serif] text-[16px] leading-[24px] flex items-center">
              Integrations & APIs
            </div>
            <div className="bg-white"></div>
            <div className="bg-white relative">
              <div className="absolute inset-0 border-l border-r border-black pointer-events-none"></div>
            </div>
            <div className="bg-white"></div>

            {/* Integrations & APIs Features */}
            {integrationsFeatures.map((feature, index) => (
              <React.Fragment key={feature.name}>
                {/* Add separator lines for Gmail API, Automatic List Cleanup, and Membership Plugin Integration */}
                {(index === 1 || index === 2 || index === 3) && (
                  <>
                    <div className="col-span-4 border-t border-gray-300 mx-auto" style={{ width: '1202px' }}></div>
                  </>
                )}
                <div className={`bg-slate-50 px-4 border-t-0 text-[#27272A] font-['Inter',_sans-serif] text-[16px] font-semibold leading-[24px] flex items-center ${index === 0 ? 'pt-4 pb-4' : 'py-4'}`}>
                  <div className="flex items-center">
                    {feature.name}
                    {feature.tooltipMsg ? (
                      <div className="relative group ml-2">
                        <img src={feature.icon} alt="" className="w-4 h-4 cursor-help" />
                        <div className="absolute bottom-full left-0 mb-2 px-4 py-3 bg-gray-800 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-50" style={{ width: '320px', fontWeight: '400', transform: 'translateX(-50%)' }}>
                          {feature.tooltipMsg}
                          <div className="absolute top-full transform -translate-x-1/2 border-4 border-transparent border-t-gray-800" style={{ left: '52%' }}></div>
                        </div>
                      </div>
                    ) : (
                      <img src={feature.icon} alt="" className="w-4 h-4 ml-2" />
                    )}
                  </div>
                </div>
                <div className={`bg-white px-3 border-t-0 flex items-start justify-start font-medium ${index === 0 ? 'pt-4 pb-4' : 'py-4'} ${(index === 2 || index === 3) ? 'text-[14px]' : ''}`}>{feature.free}</div>
                <div className={`bg-white px-3 border-t-0 flex items-start justify-start relative font-medium ${index === 0 ? 'pt-4 pb-4' : 'py-4'} ${(index === 2 || index === 3) ? 'text-[14px]' : ''}`}>
                  {feature.pro}
                  {/* Add border overlay for Pro column */}
                  <div className="absolute inset-0 border-l border-r border-black pointer-events-none"></div>
                </div>
                <div className={`bg-white px-3 border-t-0 flex items-start justify-start font-medium ${index === 0 ? 'pt-4 pb-4' : 'py-4'} ${(index === 2 || index === 3) ? 'text-[14px]' : ''}`}>{feature.max}</div>
              </React.Fragment>
            ))}
            {/* Help & Support Header Row */}
            <div className="bg-slate-50 px-3 pt-4 text-[#52525B] font-normal font-['Inter',_sans-serif] text-[16px] leading-[24px] flex items-center">
              Help & Support
            </div>
            <div className="bg-white"></div>
            <div className="bg-white relative">
              <div className="absolute inset-0 border-l border-r border-black pointer-events-none"></div>
            </div>
            <div className="bg-white"></div>

            {/* Help & Support Features */}
            {supportFeatures.map((feature, index) => (
              <React.Fragment key={feature.name}>
                <div className={`bg-slate-50 px-3 ${index === 0 ? '' : 'border-t border-slate-200'} text-[#27272A] font-['Inter',_sans-serif] text-[16px] font-semibold leading-[24px] flex items-center ${index === 0 ? 'pt-4 pb-4' : 'py-4'}`}>
                  <div className="flex items-center">
                    {feature.name}
                    {feature.tooltipMsg ? (
                      <div className="relative group ml-2">
                        <img src={feature.icon} alt="" className="w-4 h-4 cursor-help" />
                        <div className="absolute bottom-full left-0 mb-2 px-4 py-3 bg-gray-800 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-50" style={{ width: '320px', fontWeight: '400', transform: 'translateX(-50%)' }}>
                          {feature.tooltipMsg}
                          <div className="absolute top-full transform -translate-x-1/2 border-4 border-transparent border-t-gray-800" style={{ left: '52%' }}></div>
                        </div>
                      </div>
                    ) : (
                      <img src={feature.icon} alt="" className="w-4 h-4 ml-2" />
                    )}
                  </div>
                </div>
                <div className={`bg-white px-3 ${index === 0 ? '' : 'border-t border-slate-200'} flex items-center justify-start font-medium ${index === 0 ? 'pt-4 pb-4 text-[14px]' : 'py-4'}`}>{feature.free}</div>
                <div className={`bg-white px-3 ${index === 0 ? '' : 'border-t border-slate-200'} ${index === supportFeatures.length - 1 ? 'border-b border-b-black' : ''} flex items-center justify-start relative font-medium ${index === 0 ? 'pt-4 pb-4 text-[14px]' : 'py-4'}`}>
                  {feature.pro}
                  {/* Add border overlay for Pro column */}
                  <div className="absolute inset-0 border-l border-r border-black pointer-events-none"></div>
                </div>
                <div className={`bg-white px-3 ${index === 0 ? '' : 'border-t border-slate-200'} flex items-center justify-start font-medium ${index === 0 ? 'pt-4 pb-4 text-[14px]' : 'py-4'}`}>{feature.max}</div>
              </React.Fragment>
            ))}
          </>}
        </div>
      </div>

      {/* Testimonials Section - always visible after pricing plans */}
      <section className="w-full flex flex-col items-center justify-center py-8" style={{background: '#f6f5f8'}}>
        <h2 className="font-semibold text-zinc-800 text-[24px] leading-[32px] tracking-[-0.144px] mb-7 text-center">Loved by thousands of customers</h2>
        <div className="w-full flex flex-col items-center">
          <div ref={sliderRef} className="w-full keen-slider">
              {/* Testimonial 1 */}
              <div className="keen-slider__slide bg-white flex flex-col gap-6 items-start justify-between overflow-clip p-[16px] relative rounded-xl shrink-0 w-[504px] h-[320px]">
                <div className="flex flex-col gap-4 w-full">
                  <div className="flex gap-1 items-center">
                    {[...Array(5)].map((_, i) => (
                      <img key={i} src={`${adminData.baseUrl}/images/star-rating.svg`} alt="star" className="w-5 h-5" />
                    ))}
                  </div>
                  <div className="font-semibold text-zinc-800 text-[18px] leading-[28px]">Perfect plugin for blog promotion</div>
                  <div className="font-medium text-zinc-600 text-[16px] leading-[24px]">
                    “This plugin works great in WordPress. Simple, yet effective. When a new blog is released, it sends a customized email along with a link to the blog title. Great to stimulate web traffic, yet sends a simple email. Have been using for over 6 months.”
                  </div>
                </div>
                <div className="flex gap-3 items-center justify-start">
                  <div className="bg-center bg-cover bg-no-repeat rounded-[30px] shrink-0 w-12 h-12" style={{backgroundImage: `url('${adminData.baseUrl}/images/resolve-image.png')`}} />
                  <div className="flex flex-col items-start justify-center">
                    <div className="text-[14px] text-zinc-800 leading-[20px] whitespace-pre">Resolve</div>
                    <div className="text-[12px] text-zinc-400 leading-[20px] whitespace-pre">Head of Product</div>
                  </div>
                </div>
              </div>
              {/* Testimonial 2 */}
              <div className="keen-slider__slide bg-white flex flex-col gap-6 items-start justify-between overflow-clip p-[16px] relative rounded-xl shrink-0 w-[504px] h-[320px]">
                <div className="flex flex-col gap-4 w-full">
                  <div className="flex gap-1 items-center">
                    {[...Array(5)].map((_, i) => (
                      <img key={i} src={`${adminData.baseUrl}/images/star-rating.svg`} alt="star" className="w-5 h-5" />
                    ))}
                  </div>
                  <div className="font-semibold text-zinc-800 text-[18px] leading-[28px]">Great for Professional Bloggers</div>
                  <div className="font-medium text-zinc-600 text-[16px] leading-[24px]">
                    “Great for Professional Bloggers and great support! Icegram was very responsive to our questions. I highly recommend this WordPress plugin and the PAID version is worth the cost. The paid version shows intuitive stats and drill-down information.”
                  </div>
                </div>
                <div className="flex gap-3 items-center justify-start">
                  <div className="bg-center bg-cover bg-no-repeat rounded-[30px] shrink-0 w-12 h-12" style={{backgroundImage: `url('${adminData.baseUrl}/images/testimonial-rick-avatar.png')`}} />
                  <div className="flex flex-col items-start justify-center">
                    <div className="text-[14px] text-zinc-800 leading-[20px] whitespace-pre">Rick Vidallon</div>
                    <div className="text-[12px] text-zinc-400 leading-[20px] whitespace-pre">Blogger</div>
                  </div>
                </div>
              </div>
              {/* Testimonial 3 - half visible */}
              <div className="keen-slider__slide bg-white flex flex-col gap-6 items-start justify-between overflow-clip p-[16px] relative rounded-xl shrink-0 w-[252px] h-[320px]">
                <div className="flex flex-col gap-4 w-full">
                  <div className="flex gap-1 items-center">
                    {[...Array(5)].map((_, i) => (
                      <img key={i} src={`${adminData.baseUrl}/images/star-rating.svg`} alt="star" className="w-5 h-5" />
                    ))}
                  </div>
                  <div className="font-semibold text-zinc-800 text-[18px] leading-[28px]">Great for Professional Bloggers</div>
                  <div className="font-medium text-zinc-600 text-[16px] leading-[24px]">
                    “Easy setup and instant action. The best part? It actually delivers results! Unlike those big shots, it nails blog entry notifications. Plus, pair it with Icegram Collect for an even better form makeover!”
                  </div>
                </div>
                <div className="flex gap-3 items-center justify-start">
                  <div className="bg-center bg-cover bg-no-repeat rounded-[30px] shrink-0 w-12 h-12" style={{backgroundImage: `url('${adminData.baseUrl}/images/testimonial-lauren-avatar.png')`}} />
                  <div className="flex flex-col items-start justify-center">
                    <div className="text-[14px] text-zinc-800 leading-[20px] whitespace-pre">Lauren Devine</div>
                    <div className="text-[12px] text-zinc-400 leading-[20px] whitespace-pre">Artist</div>
                  </div>
                </div>
              </div>
          </div>
          {/* Carousel Indicators - now outside keen-slider for visibility */}
          <div className="flex items-center justify-center mt-6">
            <div className="relative w-[85px] h-2 cursor-pointer flex">
              <div className="absolute left-0 top-0 w-full h-2 bg-zinc-200 rounded-[100px]" />
              <div
                className="absolute left-0 top-0 h-2 bg-zinc-800 rounded-[100px] transition-all duration-300"
                style={{ width: `${(currentSlide + 1) * (85 / 3)}px` }}
              />
              {[0, 1, 2].map((idx) => (
                <div
                  key={idx}
                  className="absolute top-0 h-2"
                  style={{ left: `${idx * (85 / 3)}px`, width: `${85 / 3}px`, zIndex: 2 }}
                  onClick={() => instanceRef.current?.moveToIdx(idx)}
                />
              ))}
            </div>
          </div>
        </div>
  </section>
      {/* Trusted Team Section - Figma design */}
  <section className="w-full flex flex-col items-center justify-center py-5" style={{background: '#F6F5F8'}}>
  <div className="font-normal text-[16px] leading-[24px] text-center text-slate-500 w-full mb-6">Trusted by world’s most innovative teams</div>
  <div className="flex flex-wrap items-center justify-between gap-4 w-full mx-auto" style={{maxWidth: '1232px'}}>
          <div className="h-9 w-[111.6px] flex items-center justify-center">
            <img src={adminData.baseUrl + "/images/codingstreets-1.png"} alt="Company Logo" className="block max-w-none h-9 w-full" />
          </div>
          <div className="h-9 w-[108.9px] flex items-center justify-center">
            <img src={adminData.baseUrl + "/images/download-1.png"} alt="Company Logo" className="block max-w-none h-9 w-full" />
          </div>
          <div className="h-9 w-[99px] flex items-center justify-center">
            <img src={adminData.baseUrl + "/images/logo_IGI-1.png"} alt="Company Logo" className="block max-w-none h-9 w-full" />
          </div>
          <div className="h-9 w-[123.3px] flex items-center justify-center">
            <img src={adminData.baseUrl + "/images/uslegal-logo-1.png"} alt="Company Logo" className="block max-w-none h-9 w-full" />
          </div>
          <div className="flex gap-2 h-8 items-center justify-center">
            <img src={adminData.baseUrl + "/images/GoDaddy-Academy-1.png"} alt="Netdot" className="block max-w-none h-9 w-full" />
          </div>
        </div>
      </section>
      {/* Help & Support Section - Figma design */}
      <section className="w-full flex flex-col items-center justify-center py-8" style={{background: '#f6f5f8'}}>
        <div className="font-semibold text-black text-[24px] leading-[32px] tracking-tight w-full text-left" style={{marginBottom: '16px'}}>Help & support</div>
          <div className="flex flex-row gap-0 items-stretch justify-center w-full mx-auto" >
          {/* Contact Card */}
          <div className="bg-white flex flex-col items-start justify-start p-6 rounded-bl-xl rounded-tl-xl w-[340px] border border-slate-200 shadow-sm flex-shrink-0">
            <div className="flex gap-2 items-center">
              <img src={`${adminData.baseUrl}/images/help-contact-icon.svg`} alt="Contact" className="w-6 h-6" />
              <span className="font-semibold text-[18px] leading-[28px] text-zinc-800">Reach us out for any queries</span>
            </div>
            <div className="font-medium text-[14px] leading-[24px] text-zinc-400" style={{marginTop: '12px'}}>Have questions for us, email us and our team of experts will get back to you within 24 hours</div>
            <a 
              href="mailto:hello@icegram.com?subject=Support Request - Icegram Express"
              className="border px-4 py-2 rounded-lg text-sm font-medium shadow-sm flex items-center gap-2 w-fit transition-colors duration-200 text-decoration-none" style={{"color": "#5E19CF", "borderColor": "#5E19CF", marginTop: '24px'}}
            >
              <img src={`${adminData.baseUrl}/images/help-email-icon.svg`} alt="Email" className="w-5 h-5" />
              Email us
            </a>
          </div>
          {/* FAQ Card */}
          <div className="bg-white flex flex-col items-start justify-start p-6 rounded-br-xl rounded-tr-xl flex-grow min-w-[400px] border border-slate-200 shadow-sm">
            <div className="flex gap-2 items-center">
              <img src={`${adminData.baseUrl}/images/help-faq-icon.svg`} alt="FAQ" className="w-6 h-6" />
              <span className="font-semibold text-[18px] leading-[28px] text-zinc-800">FAQ</span>
            </div>
            <div className="font-medium text-[14px] leading-[24px] text-zinc-400" style={{marginTop: '12px'}}>Find solutions to your queries</div>
            <div className="w-full border-b border-[#E4E4E7]" style={{marginTop: '20px', marginBottom: '20px'}}></div>
            {/* FAQ List */}
            <div className="w-full flex flex-col gap-0">
              <div style={{display: 'flex', flexDirection: 'column', gap: '10px'}}>
                {[
                  {
                    q: "What is Icegram, and how does it work?",
                  },
                  {
                    q: "Is Icegram free to use, or does it have premium plans?",
                  },
                  {
                    q: "How does Icegram compare to other lead generation and engagement tools?",
                  },
                  {
                    q: "Can I use Icegram on multiple websites?",
                  },
                  {
                    q: "Does Icegram slow down my website?",
                  },
                ].map((item, idx) => (
                  <React.Fragment key={item.q}>
                    <div
                      className="flex items-center justify-between w-full py-3 cursor-pointer"
                      onClick={() => setOpenFaqIdx(openFaqIdx === idx ? null : idx)}
                    >
                      <span className={`font-semibold text-[14px] leading-[20px] ${idx === 0 ? 'text-zinc-800' : 'text-[#111027]'}`}>{item.q}</span>
                      {openFaqIdx === idx ? (
                        // Minus icon from Figma
                        <img src={`${adminData.baseUrl}/images/help-minus-icon.svg`} alt="Collapse" className="w-5 h-5" />
                      ) : (
                        // Plus icon from Figma
                        <img src={`${adminData.baseUrl}/images/help-plus-icon.svg`} alt="Expand" className="w-5 h-5" />
                      )}
                    </div>
                    {openFaqIdx === idx && (
                      <div className="font-normal text-[14px] leading-[20px] text-zinc-500 pb-3">
                        Icegram is a powerful WordPress plugin that helps you create popups, opt-ins, call-to-actions, and notifications to engage website visitors and generate leads. It allows you to design and trigger different types of messages to increase conversions, reduce bounce rates, and enhance user interaction.
                      </div>
                    )}
                    <img src={`${adminData.baseUrl}/images/help-divider-line.svg`} alt="Divider" className="w-full h-[1px]" />
                  </React.Fragment>
                ))}
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
