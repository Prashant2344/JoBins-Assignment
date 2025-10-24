import axios from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api/v1';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

export const clientService = {
  // Get all clients with optional filters
  getClients: (params = {}) => {
    return api.get('/clients', { params });
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
  importCsv: (file) => {
    const formData = new FormData();
    formData.append('csv_file', file);
    return api.post('/clients/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
  },

  // Export CSV
  exportCsv: (params = {}) => {
    return api.get('/clients/export', {
      params,
      responseType: 'blob',
    });
  },

  // Get duplicate groups
  getDuplicateGroups: () => {
    return api.get('/clients/duplicates/groups');
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
