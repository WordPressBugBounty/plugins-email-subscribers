// Dashboard API service for Icegram Express
import { makeApiRequest, getBaseUrl } from './client';

// Campaign interface
export interface Campaign {
  id: string;
  slug: string;
  name: string;
  type: 'workflow' | 'post_notification' | 'newsletter' | 'workflow_email' | 'sequence';
  parent_id: string | null;
  parent_type: string | null;
  subject: string | null;
  body: string;
  from_name: string;
  from_email: string;
  reply_to_name: string;
  reply_to_email: string;
  categories: string;
  list_ids: string;
  base_template_id: string;
  status: string; // '0' = inactive, '1' = active
  meta: string | null;
  created_at: string;
  updated_at: string | null;
  deleted_at: string | null;
}

// Form interface
export interface Form {
  id: string;
  name: string;
  body: string;
  settings: string;
  styles: string;
  preview_image: string;
  created_at: string;
  updated_at: string | null;
  deleted_at: string | null;
  af_id: string;
  subscriber_count: number; 
  list_names: string; 
}

// List interface
export interface List {
  id: string;
  slug: string;
  name: string;
  description: string;
  hash: string;
  created_at: string;
  updated_at: string | null;
  deleted_at: string | null;
}

// Campaign KPI metrics interface
export interface CampaignKpi {
  id: number;
  total_sent: string | number;
  total_opens: string | number;
  total_clicks: string | number;
  total_unsubscribe?: string | number;
  title?: string;
  hash?: string;
  status?: string;
  campaign_type?: string;
  type?: string;
  campaign_opens_rate?: string | number;
  campaign_clicks_rate?: string | number;
  campaign_losts_rate?: string | number;
  start_at?: string;
  finish_at?: string;
}

// Dashboard KPI interface
export interface DashboardKpi {
  campaigns: CampaignKpi[];
  total_subscribed: string;
  total_email_opens: string;
  total_links_clicks: string;
  total_message_sent: string;
  total_unsubscribed: string;
  avg_open_rate: string;
  avg_click_rate: string;
  avg_unsubscribe_rate: string;
  contacts_growth: Record<string, number>;
  avg_bounce_rate: number;
  total_hard_bounced_contacts: string;
  hard_bounces_before_two_months: string;
  hard_bounces_percentage_growth: number;
  sent_percentage_growth: number;
  sent_before_two_months: string;
  open_percentage_growth: number;
  open_before_two_months: string;
  click_percentage_growth: number;
  click_before_two_months: string;
  unsubscribe_percentage_growth: number;
}

// Plugin data interface - Updated name for better clarity
export interface PluginData {
  slug: string; // e.g., "icegram-mailer/icegram-mailer.php"
  name: string; // e.g., "Icegram Mailer"
  description: string; // Plugin description
  plugin_url: string; // WordPress.org plugin URL
  is_installed: number; // 1 for installed, 0 for not installed
  is_active: number | string; // 1 for active, empty string for inactive
  install_url: string; // URL for installing the plugin
  activate_url: string; // URL for activating the plugin
  status_text: string; // "Active", "Installed", etc.
  action_text: string; // "Active", "Activate", etc.
  action_url: string; // URL for the action (empty if no action needed)
}

// Complete dashboard data interface
export interface DashboardData {
  campaigns: Campaign[];
  audience_activity: any[];
  forms: Form[];
  lists: List[];
  dashboard_kpi: DashboardKpi;
  plan?: 'trial' | 'lite' | 'starter' | 'pro';
  icegram_plugins?: PluginData[];
}

// Get complete dashboard data
export const getDashboardData = async (): Promise<DashboardData> => {
  
  const response = await makeApiRequest<DashboardData>('dashboard', 'get_dashboard_data');

  if (!response.success) {
    throw new Error(response.message || 'Failed to fetch dashboard data');
  }

  return response.data;
};

// Utility functions for data formatting
export const parseIntSafe = (value: string | number): number => typeof value === 'number' ? value : parseInt(String(value)) || 0;
export const parseFloatSafe = (value: string | number): number => typeof value === 'number' ? value : parseFloat(String(value)) || 0;
export const formatNumber = (num: number): string => num.toLocaleString();
export const formatPercentage = (rate: number | string): string => {
  const numericRate = typeof rate === 'number' ? rate : parseFloat(String(rate)) || 0;
  return `${numericRate.toFixed(1)}%`;
};

// Helper function to get base WordPress admin URL
// Helper function to get plugin button text and URL based on status
export const getPluginButtonInfo = (plugin: PluginData) => {
  const base = getBaseUrl();
  const actionText = plugin.action_text || plugin.status_text;
  const url = `${base}/wp-admin/plugin-install.php?s=icegram&tab=search&type=term`;
  
  if (actionText === "Active") {
    return { text: "Active", url, variant: "activated" as const };
  } else if (actionText === "Activate") {
    return { text: "Activate", url, variant: "activate" as const };
  } else {
    return { text: "Install", url, variant: "install" as const };
  }
};

// Workflow creation response interface
export interface WorkflowCreationResponse {
  workflow_id: number;
  edit_url: string;
  message: string;
}

export const createDashboardWorkflow = async (workflowType: 'welcome-email' | 'confirmation-email' | 'abandoned-cart'): Promise<WorkflowCreationResponse> => {
  const workflowTypeMap = {
    'welcome-email': 'welcome-email',
    'confirmation-email': 'confirmation-email',
    'abandoned-cart': 'abandoned-cart'
  };
  
  const backendWorkflowType = workflowTypeMap[workflowType];
  
  const response = await makeApiRequest<WorkflowCreationResponse>('dashboard', 'create_dashboard_workflow', {
    workflow_type: backendWorkflowType,
    security: (window as any).es_admin_ajax_nonce || 'mock-nonce'
  });
  
  if (!response.success) {
    throw new Error(response.message || 'Failed to create workflow');
  }
  
  // Handle WordPress router double-nested structure
  let workflowData = response.data;
  
  // Check if the nested data has success property
  if (workflowData && typeof workflowData === 'object' && 'success' in workflowData) {
    if (!workflowData.success) {
      throw new Error(workflowData.message || 'Workflow creation failed');
    }
  }
  
  const workflowResult = {
    workflow_id: workflowData.workflow_id,
    edit_url: workflowData.edit_url,
    message: workflowData.message || 'Workflow created successfully!'
  };
  
  // Validate that we have the required fields
  if (!workflowResult.workflow_id || !workflowResult.edit_url) {
    throw new Error('Backend response missing required fields (workflow_id or edit_url)');
  }
  
  return workflowResult;
};

// Check if user can create abandoned cart workflow (Pro feature only)
// Valid plans: trial, lite, starter, pro
export const canCreateAbandonedCartWorkflow = (userPlan: string): boolean => {
  const plan = userPlan.toLowerCase();
  return plan === 'pro';
};

export const getPricingPageUrl = (): string => {
  const baseUrl = getBaseUrl();
  return `${baseUrl}/wp-admin/admin.php?page=es_pricing#pricing`;
};

// Get subscribers stats with filters
export const getSubscribersStats = async (days: number, listId?: string): Promise<DashboardKpi> => {
  const requestData = {
    page: 'es_dashboard',
    days: days.toString(),
    list_id: listId || '',
    override_cache: true
  };

  const response = await makeApiRequest<any>('dashboard', 'get_subscribers_stats', requestData);
  
  if (!response.success) {
    throw new Error(response.message || 'Failed to fetch stats');
  }

  // Return the dashboard_kpi data from the response
  return response.data?.dashboard_kpi || response.data;
};

// Onboarding options interface
export interface OnboardingOptions {
  sendFirstCampaign: boolean;
  importContacts: boolean;
  createSubscriptionForm: boolean;
  createWorkflow: boolean;
}

// Save onboarding step to WordPress options
export const saveOnboardingStep = async (stepName: keyof OnboardingOptions, value: boolean): Promise<void> => {
  const requestData = {
    step_name: stepName,
    value: value ? 'yes' : 'no'
  };

  const response = await makeApiRequest('dashboard', 'save_onboarding_step', requestData);
  
  // Handle nested response structure
  const result = response.data?.data || response.data;
  const success = response.data?.success ?? response.success;
  
  if (!success) {
    throw new Error(result?.message || response.message || 'Failed to save onboarding step');
  }
};

// Get all onboarding steps from WordPress options
export const getOnboardingSteps = async (): Promise<OnboardingOptions> => {
  const response = await makeApiRequest<{success: boolean, data: OnboardingOptions}>('dashboard', 'get_onboarding_steps');
  
  console.log('Onboarding API Response:', response);
  
  if (!response.success) {
    throw new Error(response.message || 'Failed to get onboarding steps');
  }

  // Handle nested data structure from router
  const onboardingData = response.data?.data || response.data;
  console.log('Onboarding data extracted:', onboardingData);

  return onboardingData || {
    sendFirstCampaign: false,
    importContacts: false,
    createWorkflow: false,
    createSubscriptionForm: false
  };
};
