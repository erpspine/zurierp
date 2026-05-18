import React from 'react';
import { Box, Grid, Paper, Typography, useTheme, Chip } from '@mui/material';
import { PieChart, Pie, Cell, ResponsiveContainer, LineChart, Line, XAxis, YAxis, Tooltip, CartesianGrid } from 'recharts';

const pieData = [
  { name: '19-20 years', value: 68 },
  { name: '20-21 years', value: 58 },
  { name: '21-22 years', value: 78 },
  { name: '22-23 years', value: 88 },
];
const COLORS = ['#00e5ff', '#ff3cac', '#ffea00', '#00ffb0'];

const lineData = [
  { name: 'Mon', uv: 400 },
  { name: 'Tue', uv: 300 },
  { name: 'Wed', uv: 200 },
  { name: 'Thu', uv: 278 },
  { name: 'Fri', uv: 189 },
  { name: 'Sat', uv: 239 },
  { name: 'Sun', uv: 349 },
];

const Dashboard = () => {
  const theme = useTheme();
  return (
    <Box sx={{ flexGrow: 1, p: 3, bgcolor: 'background.default', minHeight: '100vh' }}>
      <Grid container spacing={3}>
        <Grid item xs={12} md={4}>
          <Paper sx={{ p: 3, background: 'linear-gradient(135deg, #23283a 60%, #00e5ff22 100%)', boxShadow: 6 }}>
            <Typography variant="h6" color="primary">Yearly Earning</Typography>
            <ResponsiveContainer width="100%" height={200}>
              <PieChart>
                <Pie data={pieData} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={60} fill="#00e5ff" label>
                  {pieData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
              </PieChart>
            </ResponsiveContainer>
            <Box sx={{ mt: 2, display: 'flex', flexDirection: 'column', gap: 1 }}>
              {pieData.map((item, idx) => (
                <Chip key={item.name} label={`${item.name}: ${item.value}%`} size="small" sx={{ bgcolor: COLORS[idx], color: '#181c24', fontWeight: 700 }} />
              ))}
            </Box>
          </Paper>
        </Grid>
        <Grid item xs={12} md={8}>
          <Paper sx={{ p: 3, background: 'linear-gradient(135deg, #23283a 60%, #ff3cac22 100%)', boxShadow: 6 }}>
            <Typography variant="h6" color="secondary">Profit Overview</Typography>
            <ResponsiveContainer width="100%" height={200}>
              <LineChart data={lineData}>
                <CartesianGrid strokeDasharray="3 3" stroke="#333" />
                <XAxis dataKey="name" stroke="#b0b8d1" />
                <YAxis stroke="#b0b8d1" />
                <Tooltip contentStyle={{ background: '#23283a', border: 'none', color: '#fff' }} />
                <Line type="monotone" dataKey="uv" stroke="#00e5ff" strokeWidth={3} activeDot={{ r: 8, fill: '#ff3cac' }} />
              </LineChart>
            </ResponsiveContainer>
          </Paper>
        </Grid>
        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 3, minHeight: 150, background: 'linear-gradient(135deg, #23283a 60%, #ffea0022 100%)', boxShadow: 3 }}>
            <Typography variant="h6" color="warning.main">Task Overview</Typography>
            <Typography sx={{ color: 'text.secondary', mt: 1 }}>Provided Time: <b style={{ color: theme.palette.primary.main }}>6 Days</b></Typography>
            <Typography sx={{ color: 'text.secondary' }}>Working Time: <b style={{ color: theme.palette.secondary.main }}>60M</b></Typography>
          </Paper>
        </Grid>
        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 3, minHeight: 150, background: 'linear-gradient(135deg, #23283a 60%, #00ffb022 100%)', boxShadow: 3 }}>
            <Typography variant="h6" color="success.main">Project Status</Typography>
            <Typography sx={{ color: 'text.secondary', mt: 1 }}>Running: <b style={{ color: '#00e5ff' }}>5</b></Typography>
            <Typography sx={{ color: 'text.secondary' }}>Completed: <b style={{ color: '#ffea00' }}>12</b></Typography>
            <Typography sx={{ color: 'text.secondary' }}>Pending: <b style={{ color: '#ff3cac' }}>2</b></Typography>
          </Paper>
        </Grid>
      </Grid>
    </Box>
  );
};

export default Dashboard;