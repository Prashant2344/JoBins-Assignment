import React, { useState, useEffect, useCallback } from 'react';
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
  Pagination,
  Skeleton,
  IconButton,
  Tooltip,
} from '@mui/material';
import { ExpandMore, Refresh, Delete } from '@mui/icons-material';
import { DataGrid } from '@mui/x-data-grid';
import { clientService } from '../services/clientService';

function DuplicateManager({ onAction }) {
  const [duplicateGroups, setDuplicateGroups] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    has_more: false
  });
  const [expandedGroups, setExpandedGroups] = useState(new Set());
  const [groupClients, setGroupClients] = useState({});
  const [loadingClients, setLoadingClients] = useState(new Set());

  const fetchDuplicateGroups = useCallback(async (page = 1) => {
    try {
      setLoading(true);
      setError(null);
      const response = await clientService.getDuplicateGroups({
        page,
        per_page: 10
      });
      
      setDuplicateGroups(response.data.data);
      setPagination(response.data.pagination);
    } catch (err) {
      setError('Failed to fetch duplicate groups');
      console.error('Error fetching duplicate groups:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  const fetchGroupClients = useCallback(async (groupId, page = 1) => {
    try {
      setLoadingClients(prev => new Set(prev).add(groupId));
      const response = await clientService.getDuplicateGroupClients(groupId, {
        page,
        per_page: 15
      });
      
      setGroupClients(prev => ({
        ...prev,
        [groupId]: {
          clients: response.data.data,
          pagination: response.data.pagination
        }
      }));
    } catch (err) {
      console.error('Error fetching group clients:', err);
    } finally {
      setLoadingClients(prev => {
        const newSet = new Set(prev);
        newSet.delete(groupId);
        return newSet;
      });
    }
  }, []);

  useEffect(() => {
    fetchDuplicateGroups();
  }, [fetchDuplicateGroups]);

  const handlePageChange = (event, page) => {
    fetchDuplicateGroups(page);
  };

  const handleGroupExpand = (groupId) => {
    const newExpanded = new Set(expandedGroups);
    if (expandedGroups.has(groupId)) {
      newExpanded.delete(groupId);
    } else {
      newExpanded.add(groupId);
      // Load clients for this group if not already loaded
      if (!groupClients[groupId]) {
        fetchGroupClients(groupId);
      }
    }
    setExpandedGroups(newExpanded);
  };

  const handleDeleteClient = async (clientId, groupId) => {
    if (window.confirm('Are you sure you want to delete this client?')) {
      try {
        await clientService.deleteClient(clientId);
        // Refresh the specific group's clients
        if (groupClients[groupId]) {
          fetchGroupClients(groupId, groupClients[groupId].pagination.current_page);
        }
        // Refresh duplicate groups to update counts
        fetchDuplicateGroups(pagination.current_page);
        onAction();
      } catch (err) {
        setError('Failed to delete client');
      }
    }
  };

  const getGroupDisplayName = (group) => {
    if (group.representative_company) {
      return `${group.representative_company}`;
    } else if (group.representative_email) {
      return `${group.representative_email}`;
    } else if (group.representative_phone) {
      return `Phone: ${group.representative_phone}`;
    }
    return `Group ${group.group_id}`;
  };

  const getGroupSubtitle = (group) => {
    const parts = [];
    if (group.representative_email && group.representative_company !== group.representative_email) {
      parts.push(group.representative_email);
    }
    if (group.representative_phone && group.representative_company !== group.representative_phone) {
      parts.push(group.representative_phone);
    }
    return parts.length > 0 ? parts.join(' • ') : '';
  };

  const handleRefresh = () => {
    fetchDuplicateGroups(pagination.current_page);
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
        <Tooltip title="Delete Client">
          <IconButton
            size="small"
            color="error"
            onClick={() => handleDeleteClient(params.row.id, params.row.groupId)}
          >
            <Delete />
          </IconButton>
        </Tooltip>
      ),
    },
  ];

  const renderSkeleton = () => (
    <Box>
      {[...Array(3)].map((_, index) => (
        <Paper key={index} sx={{ p: 2, mb: 2 }}>
          <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
            <Skeleton variant="text" width="200px" height={32} />
            <Skeleton variant="rectangular" width={80} height={24} sx={{ ml: 2 }} />
          </Box>
          <Skeleton variant="rectangular" height={200} />
        </Paper>
      ))}
    </Box>
  );

  if (loading && duplicateGroups.length === 0) {
    return (
      <Box>
        <Typography variant="h5" gutterBottom>
          Manage Duplicates
        </Typography>
        {renderSkeleton()}
      </Box>
    );
  }

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Typography variant="h5">
          Manage Duplicates
        </Typography>
        <Button
          variant="outlined"
          startIcon={<Refresh />}
          onClick={handleRefresh}
          disabled={loading}
        >
          Refresh
        </Button>
      </Box>
      
      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}

      {duplicateGroups.length === 0 && !loading ? (
        <Paper sx={{ p: 3 }}>
          <Typography variant="h6" color="text.secondary" align="center">
            No duplicate groups found
          </Typography>
        </Paper>
      ) : (
        <Box>
          {duplicateGroups.map((group, index) => {
            const isExpanded = expandedGroups.has(group.group_id);
            const clientsData = groupClients[group.group_id];
            const isLoadingClients = loadingClients.has(group.group_id);

            return (
              <Accordion 
                key={group.group_id} 
                expanded={isExpanded}
                onChange={() => handleGroupExpand(group.group_id)}
                sx={{ mb: 2 }}
              >
                <AccordionSummary expandIcon={<ExpandMore />}>
                  <Box sx={{ display: 'flex', alignItems: 'center', width: '100%' }}>
                    <Box sx={{ flexGrow: 1 }}>
                      <Typography variant="h6" sx={{ fontWeight: 'bold' }}>
                        {getGroupDisplayName(group)}
                      </Typography>
                      {getGroupSubtitle(group) && (
                        <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
                          {getGroupSubtitle(group)}
                        </Typography>
                      )}
                    </Box>
                    <Chip 
                      label={`${group.count} duplicates`} 
                      color="secondary" 
                      sx={{ mr: 2 }}
                    />
                  </Box>
                </AccordionSummary>
                <AccordionDetails>
                  {isLoadingClients ? (
                    <Box sx={{ height: 400, width: '100%' }}>
                      <Skeleton variant="rectangular" height="100%" />
                    </Box>
                  ) : clientsData ? (
                    <Box>
                      <Paper sx={{ height: 400, width: '100%' }}>
                        <DataGrid
                          rows={clientsData.clients.map(client => ({
                            ...client,
                            groupId: group.group_id
                          }))}
                          columns={columns}
                          pageSize={15}
                          rowsPerPageOptions={[15, 30, 50]}
                          checkboxSelection={false}
                          disableSelectionOnClick
                          disableColumnMenu
                          disableColumnFilter
                          disableColumnSelector
                          density="compact"
                          loading={isLoadingClients}
                        />
                      </Paper>
                      {clientsData.pagination.last_page > 1 && (
                        <Box sx={{ display: 'flex', justifyContent: 'center', mt: 2 }}>
                          <Pagination
                            count={clientsData.pagination.last_page}
                            page={clientsData.pagination.current_page}
                            onChange={(event, page) => fetchGroupClients(group.group_id, page)}
                            color="primary"
                            size="small"
                          />
                        </Box>
                      )}
                    </Box>
                  ) : (
                    <Box sx={{ height: 400, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                      <CircularProgress />
                    </Box>
                  )}
                </AccordionDetails>
              </Accordion>
            );
          })}

          {pagination.last_page > 1 && (
            <Box sx={{ display: 'flex', justifyContent: 'center', mt: 3 }}>
              <Pagination
                count={pagination.last_page}
                page={pagination.current_page}
                onChange={handlePageChange}
                color="primary"
                size="large"
                showFirstButton
                showLastButton
              />
            </Box>
          )}

          {loading && duplicateGroups.length > 0 && (
            <Box sx={{ display: 'flex', justifyContent: 'center', mt: 2 }}>
              <CircularProgress size={24} />
            </Box>
          )}
        </Box>
      )}
    </Box>
  );
}

export default DuplicateManager;