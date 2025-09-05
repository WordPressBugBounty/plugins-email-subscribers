// Generic API client for Icegram Express

// Helper function to get base WordPress admin URL
export const getBaseUrl = (): string => {
  // WordPress admin data must be present in all cases
  const adminData = (window as any).icegramExpressAdminData;
  
  const baseUrl = adminData.apiUrl.replace('/wp-admin/admin-ajax.php', '');
  return baseUrl;
};

// Get API URL from global variable
const getApiUrl = (): string => {
  const adminData = (window as any).icegramExpressAdminData;
  return adminData?.apiUrl || '';
};

// Generic API response interface
export interface ApiResponse<T = any> {
  success: boolean;
  data: T;
  message?: string;
}

// Make API request to Icegram Express
export const makeApiRequest = async <T = any>(
  handler: string,
  method: string,
  additionalParams?: Record<string, any>
): Promise<ApiResponse<T>> => {
  const apiUrl = getApiUrl();
  const adminData = (window as any).icegramExpressAdminData;
  
  if (!apiUrl) {
    throw new Error('API URL not available');
  }

  const formData = new FormData();
  
  // Always set action for Icegram Express
  formData.append('action', 'icegram-express');
  formData.append('security', adminData.security);
  
  // Add handler and method
  formData.append('handler', handler);
  formData.append('method', method);
  
  // Standardized approach: always send additional params as JSON under 'data'
  if (additionalParams) {
    formData.append('data', JSON.stringify(additionalParams));
  }

  try {
    const response = await fetch(apiUrl, {
      method: 'POST',
      body: formData,
      // No credentials as requested
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('API request failed:', error);
    throw error;
  }
};
