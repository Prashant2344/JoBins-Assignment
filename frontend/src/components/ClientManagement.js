import React, { useState, useEffect } from 'react';
import {
  Box,
  Paper,
  Typography,
  Tabs,
  Tab,
  Alert,
  CircularProgress,
} from '@mui/material';
import { DataGrid } from '@mui/x-data-grid';
import CsvImport from './CsvImport';
import CsvExport from './CsvExport';
import DuplicateManager from './DuplicateManager';
import StatsDashboard from './StatsDashboard';
import { clientService } from '../services/clientService';

function TabPanel({ children, value, index, ...other }) {
  return (
    <div
      role="tabpanel"
      hidden={value !== index}
      id={`simple-tabpanel-${index}`}
      aria-labelledby={`simple-tab-${index}`}
      {...other}
    >
      {value === index && <Box sx={{ p: 3 }}>{children}</Box>}
    </div>
  );
}

function ClientManagement() {
  const [tabValue, setTabValue] = useState(0);
  const [clients, setClients] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const columns = [
    { field: 'id', headerName: 'ID', width: 70 },
    { field: 'company_name', headerName: 'Company Name', width: 200 },
    { field: 'email', headerName: 'Email', width: 250 },
    { field: 'phone_number', headerName: 'Phone Number', width: 150 },
    { 
      field: 'is_duplicate', 
      headerName: 'Duplicate', 
      width: 100,
      renderCell: (params) => (
        <span style={{ color: params.value ? '#f44336' : '#4caf50' }}>
          {params.value ? 'Yes' : 'No'}
        </span>
      )
    },
    { 
      field: 'duplicate_group_id', 
      headerName: 'Group ID', 
      width: 200,
      renderCell: (params) => params.value || '-'
    },
    { field: 'created_at', headerName: 'Created At', width: 180 },
  ];

  useEffect(() => {
    fetchClients();
    fetchStats();
  }, []);

  const fetchClients = async () => {
    try {
      setLoading(true);
      const response = await clientService.getClients();
      // The API returns paginated data, so we need to access response.data.data.data
      setClients(response.data.data.data || []);
    } catch (err) {
      setError('Failed to fetch clients');
      console.error('Error fetching clients:', err);
    } finally {
      setLoading(false);
    }
  };

  const fetchStats = async () => {
    try {
      const response = await clientService.getStats();
      setStats(response.data.data);
    } catch (err) {
      console.error('Failed to fetch stats:', err);
    }
  };

  const handleTabChange = (event, newValue) => {
    setTabValue(newValue);
  };

  const handleImportSuccess = () => {
    fetchClients();
    fetchStats();
  };

  const handleExportSuccess = () => {
    // Could show a success message
  };

  const handleDuplicateAction = () => {
    fetchClients();
    fetchStats();
  };

  if (loading && clients.length === 0) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Box>
      <Typography variant="h4" component="h1" gutterBottom>
        Client Management System
      </Typography>
      
      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
        <Tabs value={tabValue} onChange={handleTabChange} aria-label="client management tabs">
          <Tab label="Dashboard" />
          <Tab label="Import CSV" />
          <Tab label="Export CSV" />
          <Tab label="Manage Duplicates" />
          <Tab label="All Clients" />
        </Tabs>
      </Box>

      <TabPanel value={tabValue} index={0}>
        <StatsDashboard stats={stats} />
      </TabPanel>

      <TabPanel value={tabValue} index={1}>
        <CsvImport onSuccess={handleImportSuccess} />
      </TabPanel>

      <TabPanel value={tabValue} index={2}>
        <CsvExport onSuccess={handleExportSuccess} />
      </TabPanel>

      <TabPanel value={tabValue} index={3}>
        <DuplicateManager onAction={handleDuplicateAction} />
      </TabPanel>

      <TabPanel value={tabValue} index={4}>
        <Paper sx={{ height: 600, width: '100%' }}>
          <DataGrid
            rows={clients}
            columns={columns}
            initialState={{
              pagination: {
                paginationModel: { page: 0, pageSize: 10 },
              },
            }}
            pageSizeOptions={[10, 25, 50]}
            checkboxSelection
            disableRowSelectionOnClick
            loading={loading}
          />
        </Paper>
      </TabPanel>
    </Box>
  );
}

export default ClientManagement;
