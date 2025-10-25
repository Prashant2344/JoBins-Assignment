import React, { useState, useEffect } from 'react';
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
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Chip,
  Accordion,
  AccordionSummary,
  AccordionDetails,
} from '@mui/material';
import { CloudUpload, ExpandMore, Error, Warning } from '@mui/icons-material';
import { clientService } from '../services/clientService';

function CsvImport({ onSuccess }) {
  const [file, setFile] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [result, setResult] = useState(null);
  const [error, setError] = useState(null);
  const [batchConfig, setBatchConfig] = useState({
    batchSize: 1000,
    maxErrors: 100
  });
  const [showAdvanced, setShowAdvanced] = useState(false);

  const handleFileChange = (event) => {
    const selectedFile = event.target.files[0];
    setFile(selectedFile);
    setError(null);
    setResult(null);
  };

  // Load batch configuration on component mount
  useEffect(() => {
    const loadBatchConfig = async () => {
      try {
        const response = await clientService.getBatchConfig();
        if (response.data.success) {
          setBatchConfig({
            batchSize: response.data.data.batch_size,
            maxErrors: response.data.data.max_errors
          });
        }
      } catch (err) {
        console.warn('Could not load batch configuration:', err);
      }
    };
    loadBatchConfig();
  }, []);

  const handleUpload = async () => {
    if (!file) {
      setError('Please select a CSV file');
      return;
    }

    try {
      setUploading(true);
      setError(null);
      
      // Create FormData with batch configuration
      const formData = new FormData();
      formData.append('csv_file', file);
      formData.append('batch_size', batchConfig.batchSize);
      formData.append('max_errors', batchConfig.maxErrors);
      
      const response = await clientService.importCsv(formData);
      setResult(response.data);
      
      if (response.data.success) {
        setTimeout(() => {
          onSuccess();
        }, 100);
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
        
        <Box sx={{ mt: 2 }}>
          <Button
            variant="text"
            onClick={() => setShowAdvanced(!showAdvanced)}
            endIcon={<ExpandMore />}
          >
            Advanced Settings
          </Button>
          
          {showAdvanced && (
            <Box sx={{ mt: 2, p: 2, bgcolor: 'grey.50', borderRadius: 1 }}>
              <Grid container spacing={2}>
                <Grid item xs={12} sm={6}>
                  <FormControl fullWidth>
                    <InputLabel>Batch Size</InputLabel>
                    <Select
                      value={batchConfig.batchSize}
                      onChange={(e) => setBatchConfig(prev => ({ ...prev, batchSize: e.target.value }))}
                      label="Batch Size"
                    >
                      <MenuItem value={500}>500 records</MenuItem>
                      <MenuItem value={1000}>1,000 records (recommended)</MenuItem>
                      <MenuItem value={2000}>2,000 records</MenuItem>
                      <MenuItem value={5000}>5,000 records</MenuItem>
                    </Select>
                  </FormControl>
                </Grid>
                <Grid item xs={12} sm={6}>
                  <FormControl fullWidth>
                    <InputLabel>Max Errors</InputLabel>
                    <Select
                      value={batchConfig.maxErrors}
                      onChange={(e) => setBatchConfig(prev => ({ ...prev, maxErrors: e.target.value }))}
                      label="Max Errors"
                    >
                      <MenuItem value={50}>50 errors</MenuItem>
                      <MenuItem value={100}>100 errors (recommended)</MenuItem>
                      <MenuItem value={200}>200 errors</MenuItem>
                      <MenuItem value={500}>500 errors</MenuItem>
                    </Select>
                  </FormControl>
                </Grid>
              </Grid>
              <Typography variant="caption" color="text.secondary" sx={{ mt: 1, display: 'block' }}>
                Larger batch sizes process faster but use more memory. Processing stops after reaching max errors.
              </Typography>
            </Box>
          )}
        </Box>
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
                
                {result.data.total_rows && (
                  <Grid item xs={12}>
                    <Card>
                      <CardContent>
                        <Typography variant="h6" gutterBottom>
                          Processing Summary
                        </Typography>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, flexWrap: 'wrap' }}>
                          <Chip 
                            label={`Total Rows: ${result.data.total_rows}`} 
                            color="primary" 
                            variant="outlined" 
                          />
                          <Chip 
                            label={`Processed: ${result.data.processed_rows}`} 
                            color="success" 
                            variant="outlined" 
                          />
                          {result.data.processed_rows < result.data.total_rows && (
                            <Chip 
                              label={`Stopped Early`} 
                              color="warning" 
                              variant="outlined" 
                              icon={<Warning />}
                            />
                          )}
                        </Box>
                        {result.data.processed_rows < result.data.total_rows && (
                          <Typography variant="caption" color="warning.main" sx={{ mt: 1, display: 'block' }}>
                            Processing was stopped early due to reaching the maximum error limit.
                          </Typography>
                        )}
                      </CardContent>
                    </Card>
                  </Grid>
                )}
              </Grid>
            )}

            {result.data?.errors_details && result.data.errors_details.length > 0 && (
              <Box sx={{ mt: 2 }}>
                <Accordion>
                  <AccordionSummary expandIcon={<ExpandMore />}>
                    <Typography variant="h6" sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                      <Error color="error" />
                      Error Details ({result.data.errors_details.length} errors)
                    </Typography>
                  </AccordionSummary>
                  <AccordionDetails>
                    <Box sx={{ maxHeight: 400, overflow: 'auto' }}>
                      {result.data.errors_details.map((error, index) => (
                        <Alert 
                          key={index} 
                          severity={error.type === 'validation_error' ? 'warning' : 'error'} 
                          sx={{ mb: 1 }}
                        >
                          <Typography variant="subtitle2" gutterBottom>
                            Row {error.row}
                            {error.type === 'batch_error' && ' (Batch Error)'}
                            {error.type === 'processing_limit' && ' (Processing Limit)'}
                          </Typography>
                          
                          {error.error_messages && (
                            <Box>
                              <Typography variant="body2" component="div">
                                {error.error_messages.map((msg, msgIndex) => (
                                  <div key={msgIndex}>• {msg}</div>
                                ))}
                              </Typography>
                            </Box>
                          )}
                          
                          {error.error && (
                            <Typography variant="body2">
                              {error.error}
                            </Typography>
                          )}
                          
                          {error.data && (
                            <Box sx={{ mt: 1, p: 1, bgcolor: 'grey.100', borderRadius: 1 }}>
                              <Typography variant="caption" color="text.secondary">
                                Data: {JSON.stringify(error.data)}
                              </Typography>
                            </Box>
                          )}
                        </Alert>
                      ))}
                    </Box>
                  </AccordionDetails>
                </Accordion>
              </Box>
            )}
          </Box>
        )}
      </Paper>
    </Box>
  );
}

export default CsvImport;
