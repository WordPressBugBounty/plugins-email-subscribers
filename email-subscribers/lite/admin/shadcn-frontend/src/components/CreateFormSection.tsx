import React from 'react';
import { Button } from './ui/button';
import { Card } from './ui/card';
import {
  mailCheckIconFigma,
  contactIconFigma,
  mailOpenIconFigma,
  chevronRightIconFigma,
} from '../assets/images';

interface FormTemplate {
  id: string;
  name: string;
  icon: string;
  popular?: boolean;
}

const formTemplates: FormTemplate[] = [
  {
    id: 'subscription',
    name: 'Subscription Form',
    icon: mailCheckIconFigma,
    popular: true,
  },
  {
    id: 'contact',
    name: 'Contact Form',
    icon: contactIconFigma,
    popular: true,
  },
  {
    id: 'newsletter',
    name: 'Inline Newsletter Form',
    icon: mailOpenIconFigma,
    popular: false,
  },
];

const CreateFormSection: React.FC = () => {
  return (
    <div className="bg-white box-border content-stretch flex flex-col gap-4 items-center justify-start p-4 relative rounded-xl w-full border border-slate-200">
      {/* Header */}
      <div className="font-['Inter'] font-medium leading-6 min-w-full not-italic relative shrink-0 text-base text-left text-zinc-800">
        <p className="block leading-6">Create with our popular forms</p>
      </div>
      
      {/* Form Templates Grid */}
      <div className="box-border content-stretch flex flex-row gap-4 items-center justify-start p-0 relative shrink-0 w-full">
        {formTemplates.map((template) => (
          <Card
            key={template.id}
            className="basis-0 bg-white grow min-h-px min-w-px relative rounded-[10px] shrink-0 border border-neutral-200 p-0"
          >
            <div className="box-border content-stretch flex flex-col gap-4 items-start justify-start overflow-clip px-4 py-8 relative w-full">
              {/* Icon */}
              <div className="overflow-clip relative shrink-0 size-6">
                <img 
                  alt={template.name} 
                  className="block max-w-none size-full" 
                  src={template.icon} 
                />
              </div>
              
              {/* Form Name */}
              <div className="box-border content-stretch flex flex-row gap-2.5 items-start justify-start p-0 relative shrink-0 w-full">
                <div className="basis-0 box-border content-stretch flex flex-col gap-1.5 grow items-start justify-start min-h-px min-w-px p-0 relative shrink-0">
                  <div className="font-['Inter'] font-medium leading-6 not-italic relative shrink-0 text-base text-left text-neutral-950 w-full">
                    <p className="block leading-6">{template.name}</p>
                  </div>
                </div>
              </div>
              
              {/* Popular Badge */}
              {template.popular && (
                <div className="absolute bg-green-100 right-0 rounded-bl-[8px] rounded-tr-[8px] top-0 border border-transparent">
                  <div className="box-border content-stretch flex flex-row gap-1 items-center justify-center overflow-clip px-3 py-1.5 relative">
                    <div className="font-['Inter'] font-medium leading-4 not-italic relative shrink-0 text-xs text-left text-neutral-950 text-nowrap">
                      <p className="block leading-4 whitespace-pre">Popular</p>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </Card>
        ))}
      </div>
      
      {/* View All Button */}
      <Button
        variant="ghost"
        className="bg-transparent box-border content-stretch flex flex-row gap-2 h-9 items-center justify-center px-4 py-2 relative rounded-lg shrink-0 hover:bg-gray-50"
      >
        <div className="flex flex-col font-['Inter'] font-medium justify-center leading-5 not-italic relative shrink-0 text-sm text-left text-neutral-950 text-nowrap">
          <p className="block leading-5 whitespace-pre">View All</p>
        </div>
        <div className="overflow-clip relative shrink-0 size-4">
          <div className="absolute bottom-1/4 left-[37.5%] right-[37.5%] top-1/4">
            <div className="absolute bottom-[-8.313%] left-[-16.625%] right-[-16.625%] top-[-8.313%]">
              <img 
                alt="Chevron right" 
                className="block max-w-none size-full" 
                src={chevronRightIconFigma} 
              />
            </div>
          </div>
        </div>
      </Button>
    </div>
  );
};

export default CreateFormSection;
