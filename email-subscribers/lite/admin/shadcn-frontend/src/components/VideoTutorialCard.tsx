import { videoTutorialBg, playButtonIcon } from '../assets/images';

export default function VideoTutorialCard() {
  return (
    <div
      className="bg-white box-border content-stretch flex flex-col gap-3 items-start justify-start overflow-hidden px-[9.406px] py-3 relative rounded-[11.841px] w-full h-full"
      data-name="Card"
    >
      <div
        className="box-border content-stretch flex flex-col gap-2 items-start justify-start p-0 relative shrink-0 w-full"
        data-name="Card Content"
      >
        <div className="aspect-[2/1] rounded-[3.135px] shrink-0 w-full relative overflow-hidden">
          {/* Background Image */}
          <img 
            src={videoTutorialBg} 
            alt="Video tutorial" 
            className="absolute inset-0 w-full h-full object-cover"
          />
          
          {/* Play Button Overlay */}
          <div
            className="absolute backdrop-blur-[31.667px] backdrop-filter bg-[rgba(0,0,0,0.24)] box-border content-stretch flex flex-row gap-[11.111px] items-center justify-center left-1/2 p-[8.889px] rounded-[55.556px] top-1/2 translate-x-[-50%] translate-y-[-50%] cursor-pointer hover:bg-[rgba(0,0,0,0.3)] transition-colors"
          >
            <div
              className="overflow-clip relative shrink-0 w-[22px] h-[22px]"
              data-name="Icon / Play"
            >
              <img 
                alt="Play video" 
                className="block max-w-none w-full h-full" 
                src={playButtonIcon} 
              />
            </div>
          </div>
        </div>
      </div>
      <div
        className="box-border content-stretch flex flex-row gap-2 items-start justify-start px-3 py-0 relative shrink-0 w-full"
        data-name="Card Header"
      >
        <div
          className="basis-0 box-border content-stretch flex flex-col gap-1.5 grow items-start justify-start leading-[0] min-h-px min-w-px not-italic p-0 relative shrink-0 text-left"
          data-name="Text"
        >
          <div
            className="font-semibold relative shrink-0 text-[14.109px] text-neutral-950 w-full"
          >
            <p className="block leading-[21.947px]">How to create Forms?</p>
          </div>
          <div
            className="font-medium relative shrink-0 text-[10.974px] text-neutral-500 w-full"
          >
            <p className="block leading-[15.676px]">
              Learn how to create, customize, and manage forms effortlessly with
              our step-by-step tutorials
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
