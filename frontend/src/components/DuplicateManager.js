import React, { useState, useEffect } from 'react';
import {
  Paper,
  Typography,
  Box,
  Alert,
  CircularProgress,
  Button,
  Chip,
  Accordion,
  AccordionSummary,
  AccordionDetails,
} from '@mui/material';
import { ExpandMore } from '@mui/icons-material';
import { DataGrid } from '@mui/x-data-grid';
import { clientService } from '../services/clientService';

function DuplicateManager({ onAction }) {
  const [duplicateGroups, setDuplicateGroups] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchDuplicateGroups();
  }, []);

  const fetchDuplicateGroups = async () => {
    try {
      setLoading(true);
      const response = await clientService.getDuplicateGroups();
      setDuplicateGroups(response.data.data);
    } catch (err) {
      setError('Failed to fetch duplicate groups');
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteClient = async (clientId) => {
    if (window.confirm('Are you sure you want to delete this client?')) {
      try {
        await clientService.deleteClient(clientId);
        fetchDuplicateGroups();
        onAction();
      } catch (err) {
        setError('Failed to delete client');
      }
    }
  };

  const columns = [
    { field: 'id', headerName: 'ID', width: 70 },
    { field: 'company_name', headerName: 'Company Name', width: 200 },
    { field: 'email', headerName: 'Email', width: 250 },
    { field: 'phone_number', headerName: 'Phone Number', width: 150 },
    { field: 'created_at', headerName: 'Created At', width: 180 },
    {
      field: 'actions',
      headerName: 'Actions',
      width: 120,
      renderCell: (params) => (
        <Button
          size="small"
          color="error"
          onClick={() => handleDeleteClient(params.row.id)}
        >
          Delete
        </Button>
      ),
    },
  ];

  if (loading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Box>
      <Typography variant="h5" gutterBottom>
        Manage Duplicates
      </Typography>
      
      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      {duplicateGroups.length === 0 ? (
        <Paper sx={{ p: 3 }}>
          <Typography variant="h6" color="text.secondary" align="center">
            No duplicate groups found
          </Typography>
        </Paper>
      ) : (
        <Box>
          {duplicateGroups.map((group, index) => (
            <Accordion key={group.group_id} sx={{ mb: 2 }}>
              <AccordionSummary expandIcon={<ExpandMore />}>
                <Box sx={{ display: 'flex', alignItems: 'center', width: '100%' }}>
                  <Typography variant="h6" sx={{ flexGrow: 1 }}>
                    Duplicate Group {index + 1}
                  </Typography>
                  <Chip 
                    label={`${group.count} records`} 
                    color="secondary" 
                    sx={{ mr: 2 }}
                  />
                </Box>
              </AccordionSummary>
              <AccordionDetails>
                <Paper sx={{ height: 400, width: '100%' }}>
                  <DataGrid
                    rows={group.clients}
                    columns={columns}
                    pageSize={5}
                    rowsPerPageOptions={[5, 10]}
                    checkboxSelection
                    disableSelectionOnClick
                  />
                </Paper>
              </AccordionDetails>
            </Accordion>
          ))}
        </Box>
      )}
    </Box>
  );
}

export default DuplicateManager;
