import React from 'react';
import {
  Paper,
  Typography,
  Box,
  Grid,
  Card,
  CardContent,
  CircularProgress,
} from '@mui/material';
import {
  People,
  ContentCopy,
  CheckCircle,
  Error,
} from '@mui/icons-material';

function StatsDashboard({ stats }) {
  if (!stats) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
        <CircularProgress />
      </Box>
    );
  }

  const statCards = [
    {
      title: 'Total Clients',
      value: stats.total_clients,
      icon: <People />,
      color: 'primary',
    },
    {
      title: 'Unique Clients',
      value: stats.unique_clients,
      icon: <CheckCircle />,
      color: 'success',
    },
    {
      title: 'Duplicate Clients',
      value: stats.duplicate_clients,
      icon: <ContentCopy />,
      color: 'error',
    },
    {
      title: 'Duplicate Groups',
      value: stats.duplicate_groups,
      icon: <Error />,
      color: 'warning',
    },
  ];

  return (
    <Box>
      <Typography variant="h5" gutterBottom>
        Statistics Dashboard
      </Typography>
      
      <Grid container spacing={3}>
        {statCards.map((card, index) => (
          <Grid item xs={12} sm={6} md={3} key={index}>
            <Card>
              <CardContent>
                <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                  <Box sx={{ color: `${card.color}.main`, mr: 1 }}>
                    {card.icon}
                  </Box>
                  <Typography variant="h6" component="div">
                    {card.title}
                  </Typography>
                </Box>
                <Typography variant="h3" component="div" color={`${card.color}.main`}>
                  {card.value}
                </Typography>
              </CardContent>
            </Card>
          </Grid>
        ))}
      </Grid>

      <Paper sx={{ p: 3, mt: 3 }}>
        <Typography variant="h6" gutterBottom>
          Summary
        </Typography>
        <Typography variant="body1" paragraph>
          You have imported <strong>{stats.total_clients}</strong> client records in total.
          Of these, <strong>{stats.unique_clients}</strong> are unique records and{' '}
          <strong>{stats.duplicate_clients}</strong> are duplicates grouped into{' '}
          <strong>{stats.duplicate_groups}</strong> duplicate groups.
        </Typography>
        
        {stats.duplicate_clients > 0 && (
          <Typography variant="body2" color="text.secondary">
            Use the "Manage Duplicates" tab to review and manage duplicate records.
          </Typography>
        )}
      </Paper>
    </Box>
  );
}

export default StatsDashboard;
