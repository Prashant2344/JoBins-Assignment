import React, { useState } from 'react';
import {
  Paper,
  Typography,
  Box,
  Button,
  Alert,
  LinearProgress,
  Card,
  CardContent,
  Grid,
} from '@mui/material';
import { CloudUpload } from '@mui/icons-material';
import { clientService } from '../services/clientService';

function CsvImport({ onSuccess }) {
  const [file, setFile] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [result, setResult] = useState(null);
  const [error, setError] = useState(null);

  const handleFileChange = (event) => {
    const selectedFile = event.target.files[0];
    setFile(selectedFile);
    setError(null);
    setResult(null);
  };

  const handleUpload = async () => {
    if (!file) {
      setError('Please select a CSV file');
      return;
    }

    try {
      setUploading(true);
      setError(null);
      
      const response = await clientService.importCsv(file);
      setResult(response.data);
      
      if (response.data.success) {
        onSuccess();
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Upload failed');
    } finally {
      setUploading(false);
    }
  };

  const downloadSampleCsv = () => {
    const sampleData = [
      ['company_name', 'email', 'phone_number'],
      ['Acme Corp', 'contact@acme.com', '+1-555-0123'],
      ['Tech Solutions', 'info@techsolutions.com', '+1-555-0456'],
      ['Global Industries', 'hello@global.com', '+1-555-0789'],
    ];

    const csvContent = sampleData.map(row => row.join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'sample_clients.csv';
    link.click();
    window.URL.revokeObjectURL(url);
  };

  return (
    <Box>
      <Typography variant="h5" gutterBottom>
        Import CSV File
      </Typography>
      
      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="h6" gutterBottom>
          Upload Instructions
        </Typography>
        <Typography variant="body2" color="text.secondary" paragraph>
          Please upload a CSV file with the following columns:
        </Typography>
        <ul>
          <li><strong>company_name</strong> - Company name (required)</li>
          <li><strong>email</strong> - Email address (required, valid email format)</li>
          <li><strong>phone_number</strong> - Phone number (required)</li>
        </ul>
        
        <Button
          variant="outlined"
          onClick={downloadSampleCsv}
          sx={{ mb: 2 }}
        >
          Download Sample CSV
        </Button>
      </Paper>

      <Paper sx={{ p: 3 }}>
        <Box sx={{ mb: 2 }}>
          <input
            accept=".csv"
            style={{ display: 'none' }}
            id="csv-file-input"
            type="file"
            onChange={handleFileChange}
          />
          <label htmlFor="csv-file-input">
            <Button
              variant="contained"
              component="span"
              startIcon={<CloudUpload />}
              sx={{ mr: 2 }}
            >
              Choose CSV File
            </Button>
          </label>
          
          {file && (
            <Typography variant="body2" color="text.secondary">
              Selected: {file.name}
            </Typography>
          )}
        </Box>

        <Button
          variant="contained"
          onClick={handleUpload}
          disabled={!file || uploading}
          sx={{ mb: 2 }}
        >
          {uploading ? 'Uploading...' : 'Upload CSV'}
        </Button>

        {uploading && <LinearProgress sx={{ mb: 2 }} />}

        {error && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        )}

        {result && (
          <Box>
            <Alert 
              severity={result.success ? 'success' : 'error'} 
              sx={{ mb: 2 }}
            >
              {result.message}
            </Alert>

            {result.success && result.data && (
              <Grid container spacing={2}>
                <Grid item xs={12} md={3}>
                  <Card>
                    <CardContent>
                      <Typography variant="h6" color="primary">
                        {result.data.imported}
                      </Typography>
                      <Typography variant="body2">
                        Records Imported
                      </Typography>
                    </CardContent>
                  </Card>
                </Grid>
                
                <Grid item xs={12} md={3}>
                  <Card>
                    <CardContent>
                      <Typography variant="h6" color="secondary">
                        {result.data.duplicates}
                      </Typography>
                      <Typography variant="body2">
                        Duplicates Found
                      </Typography>
                    </CardContent>
                  </Card>
                </Grid>
                
                <Grid item xs={12} md={3}>
                  <Card>
                    <CardContent>
                      <Typography variant="h6" color="error">
                        {result.data.errors}
                      </Typography>
                      <Typography variant="body2">
                        Errors
                      </Typography>
                    </CardContent>
                  </Card>
                </Grid>
                
                <Grid item xs={12} md={3}>
                  <Card>
                    <CardContent>
                      <Typography variant="h6">
                        {Object.keys(result.data.duplicate_groups || {}).length}
                      </Typography>
                      <Typography variant="body2">
                        Duplicate Groups
                      </Typography>
                    </CardContent>
                  </Card>
                </Grid>
              </Grid>
            )}

            {result.data?.errors_details && result.data.errors_details.length > 0 && (
              <Box sx={{ mt: 2 }}>
                <Typography variant="h6" gutterBottom>
                  Error Details:
                </Typography>
                {result.data.errors_details.map((error, index) => (
                  <Alert key={index} severity="warning" sx={{ mb: 1 }}>
                    Row {error.row}: {Object.values(error.validation_errors).flat().join(', ')}
                  </Alert>
                ))}
              </Box>
            )}
          </Box>
        )}
      </Paper>
    </Box>
  );
}

export default CsvImport;
