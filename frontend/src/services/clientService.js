import axios from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api/v1';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

export const clientService = {
  getClients: (params = {}) => {
    const apiParams = {
      ...params,
      page: params.page !== undefined ? params.page + 1 : undefined,
      per_page: params.pageSize || params.per_page
    };
    
    // Remove DataGrid specific parameters
    delete apiParams.pageSize;
    
    return api.get('/clients', { params: apiParams });
  },

  // Get a specific client
  getClient: (id) => {
    return api.get(`/clients/${id}`);
  },

  // Update a client
  updateClient: (id, data) => {
    return api.put(`/clients/${id}`, data);
  },

  // Delete a client
  deleteClient: (id) => {
    return api.delete(`/clients/${id}`);
  },

  // Import CSV
  importCsv: (formData) => {
    return api.post('/clients/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
  },

  // Get batch configuration
  getBatchConfig: () => {
    return api.get('/clients/batch-config');
  },

  // Export CSV
  exportCsv: (params = {}) => {
    return api.get('/clients/export', {
      params,
      responseType: 'blob',
    });
  },

  // Get duplicate groups with pagination
  getDuplicateGroups: (params = {}) => {
    return api.get('/clients/duplicates/groups', { params });
  },

  // Get clients for a specific duplicate group
  getDuplicateGroupClients: (groupId, params = {}) => {
    return api.get(`/clients/duplicates/groups/${groupId}/clients`, { params });
  },

  // Get statistics
  getStats: () => {
    return api.get('/clients/stats');
  },

  // Delete all clients (for testing)
  deleteAllClients: () => {
    return api.delete('/clients/delete-all');
  },
};

export default api;
