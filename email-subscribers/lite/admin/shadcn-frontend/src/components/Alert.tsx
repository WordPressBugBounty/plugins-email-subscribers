import React, { useState } from 'react';
const adminData = (window as any).icegramExpressAdminData;
const images = {
  alertIcon: `${adminData.baseUrl}/images/alert-icon.svg`,
};

const Alert: React.FC = () => {
  const [isVisible, setIsVisible] = useState(true);

  const handleDismiss = () => {
    setIsVisible(false);
  };

  if (!isVisible) {
    return null;
  }

  return (
    <div
      className="bg-[#efe8fa] box-border content-stretch flex gap-3 items-center justify-start px-4 py-3 relative rounded-[10px] size-full"
      data-name="Alert"
      data-node-id="52:18340"
    >
      <div
        className="basis-0 box-border content-stretch flex gap-3 grow items-start justify-start min-h-px min-w-px p-0 relative shrink-0"
        data-name="Flex"
        data-node-id="52:18168"
      >
        <div className="h-12 relative shrink-0 w-[46.833px]" data-node-id="52:18169">
          <div className="absolute contents inset-[-1.04%_2.56%_-1.04%_4.25%]" data-name="Objects" data-node-id="52:18170">
            <div className="absolute inset-[-1.04%_2.56%_-1.04%_4.25%]" data-name="Group" data-node-id="52:18171">
              <img alt="" className="block max-w-none size-full" src={images.alertIcon} />
            </div>
          </div>
        </div>
        <div
          className="basis-0 box-border content-stretch flex flex-col gap-1 grow items-start justify-center leading-[0] min-h-px min-w-px not-italic p-0 relative shrink-0 text-[14px]"
          data-name="Div"
          data-node-id="52:18178"
        >
          <div
            className="font-['Inter:Medium',_sans-serif] font-medium overflow-ellipsis overflow-hidden relative shrink-0 text-neutral-950 text-nowrap w-full"
            data-node-id="52:18179"
          >
            <p className="[text-overflow:inherit] [text-wrap-mode:inherit]\' [white-space-collapse:inherit] block leading-[20px] overflow-inherit">
              Free course on WordPress Email Marketing Masterclass 2023
            </p>
          </div>
          <div
            className="font-['Inter:Regular',_sans-serif] font-normal relative shrink-0 text-neutral-500 w-full"
            data-node-id="52:18180"
          >
            <p className="block leading-[20px]">Learn everything related to WordPress Email Marketing  from our experts</p>
          </div>
        </div>
      </div>
      <div
        className="bg-[rgba(255,255,255,0)] box-border content-stretch flex gap-2 h-9 items-center justify-center px-4 py-2 relative rounded-lg shrink-0 cursor-pointer"
        data-name="Button"
        data-node-id="52:18181"
        onClick={handleDismiss}
      >
        <div
          className="flex flex-col font-['Inter:Medium',_sans-serif] font-medium justify-center leading-[0] not-italic relative shrink-0 text-[#5e19cf] text-[14px] text-nowrap"
          id="node-I52_18181-60_246"
        >
          <p className="block leading-[20px] whitespace-pre">Dismiss</p>
        </div>
      </div>
      <div
        className="box-border content-stretch flex gap-2 h-9 items-center justify-center px-4 py-2 relative rounded-lg shrink-0"
        data-name="Button"
        data-node-id="52:18182"
      >
        <div
          aria-hidden="true"
          className="absolute border border-[#5e19cf] border-solid inset-0 pointer-events-none rounded-lg shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)]"
        />
        <div
          className="flex flex-col font-['Inter:Medium',_sans-serif] font-medium justify-center leading-[0] not-italic relative shrink-0 text-[#5e19cf] text-[14px] text-nowrap"
          id="node-I52_18181-60_246"
        >
          <a href="#" className="block leading-[20px] whitespace-pre text-[#5e19cf] no-underline">Check it out</a>
        </div>
      </div>
    </div>
  );
};

export default Alert;
