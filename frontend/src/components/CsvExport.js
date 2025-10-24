import React, { useState } from 'react';
import {
  Paper,
  Typography,
  Box,
  Button,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Alert,
  Grid,
  Card,
  CardContent,
} from '@mui/material';
import { Download } from '@mui/icons-material';
import { clientService } from '../services/clientService';

function CsvExport({ onSuccess }) {
  const [exportType, setExportType] = useState('all');
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState(null);

  const handleExport = async () => {
    try {
      setExporting(true);
      setError(null);

      const params = {};
      if (exportType === 'duplicates') {
        params.duplicates_only = true;
      } else if (exportType === 'unique') {
        params.unique_only = true;
      }

      const response = await clientService.exportCsv(params);
      
      // Create blob and download
      const blob = new Blob([response.data], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `clients_export_${exportType}_${new Date().toISOString().split('T')[0]}.csv`;
      link.click();
      window.URL.revokeObjectURL(url);

      onSuccess();
    } catch (err) {
      setError(err.response?.data?.message || 'Export failed');
    } finally {
      setExporting(false);
    }
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>
        Export CSV File
      </Typography>
      
      <Paper sx={{ p: 3 }}>
        <Typography variant="h6" gutterBottom>
          Export Options
        </Typography>
        
        <Box sx={{ mb: 3 }}>
          <FormControl fullWidth sx={{ mb: 2 }}>
            <InputLabel>Export Type</InputLabel>
            <Select
              value={exportType}
              label="Export Type"
              onChange={(e) => setExportType(e.target.value)}
            >
              <MenuItem value="all">All Clients</MenuItem>
              <MenuItem value="unique">Unique Clients Only</MenuItem>
              <MenuItem value="duplicates">Duplicates Only</MenuItem>
            </Select>
          </FormControl>
        </Box>

        <Button
          variant="contained"
          startIcon={<Download />}
          onClick={handleExport}
          disabled={exporting}
          sx={{ mb: 2 }}
        >
          {exporting ? 'Exporting...' : 'Export CSV'}
        </Button>

        {error && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        )}

        <Grid container spacing={2} sx={{ mt: 2 }}>
          <Grid item xs={12} md={4}>
            <Card>
              <CardContent>
                <Typography variant="h6" color="primary">
                  All Clients
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  Export all client records including duplicates
                </Typography>
              </CardContent>
            </Card>
          </Grid>
          
          <Grid item xs={12} md={4}>
            <Card>
              <CardContent>
                <Typography variant="h6" color="success.main">
                  Unique Only
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  Export only unique client records
                </Typography>
              </CardContent>
            </Card>
          </Grid>
          
          <Grid item xs={12} md={4}>
            <Card>
              <CardContent>
                <Typography variant="h6" color="error">
                  Duplicates Only
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  Export only duplicate client records
                </Typography>
              </CardContent>
            </Card>
          </Grid>
        </Grid>
      </Paper>
    </Box>
  );
}

export default CsvExport;
