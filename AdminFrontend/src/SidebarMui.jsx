import React from 'react';
import { Drawer, List, ListItemButton, ListItemIcon, ListItemText, Divider, Box, Typography } from '@mui/material';
import DashboardIcon from '@mui/icons-material/Dashboard';
import BusinessIcon from '@mui/icons-material/Business';
import MonetizationOnIcon from '@mui/icons-material/MonetizationOn';
import PeopleIcon from '@mui/icons-material/People';
import AssignmentIcon from '@mui/icons-material/Assignment';
import SettingsIcon from '@mui/icons-material/Settings';
import ListAltIcon from '@mui/icons-material/ListAlt';

const drawerWidth = 240;

const menuItems = [
  { key: 'dashboard', label: 'Dashboard', icon: <DashboardIcon /> },
  { key: 'companies', label: 'Companies', icon: <BusinessIcon /> },
  { key: 'subscriptions', label: 'Subscriptions', icon: <MonetizationOnIcon /> },
  { key: 'plans', label: 'Plans', icon: <AssignmentIcon /> },
  { key: 'platform-users', label: 'Platform Users', icon: <PeopleIcon /> },
  { key: 'audit-logs', label: 'Audit Logs', icon: <ListAltIcon /> },
  { key: 'system-settings', label: 'System Settings', icon: <SettingsIcon /> },
];

const SidebarMui = ({ activePage, setActivePage }) => (
  <Drawer
    variant="permanent"
    sx={{
      width: drawerWidth,
      flexShrink: 0,
      [`& .MuiDrawer-paper`]: {
        width: drawerWidth,
        boxSizing: 'border-box',
        color: '#eafaf3',
        background: 'linear-gradient(160deg, rgba(0, 87, 53, 0.96), rgba(0, 57, 37, 0.96))',
        borderRight: '1px solid rgba(255, 255, 255, 0.14)',
      },
    }}
  >
    <Box sx={{ p: 2.2, textAlign: 'center' }}>
      <Typography variant="h6" sx={{ fontWeight: 800, color: '#f0c461', letterSpacing: 0.2 }}>ZuriTours Admin</Typography>
      <Typography variant="caption" sx={{ color: 'rgba(255,255,255,0.75)' }}>Platform Control Center</Typography>
    </Box>
    <Divider sx={{ borderColor: 'rgba(255,255,255,0.14)' }} />
    <List>
      {menuItems.map((item) => (
        <ListItemButton
          key={item.key}
          selected={activePage === item.key}
          onClick={() => setActivePage(item.key)}
          sx={{
            mx: 1,
            my: 0.4,
            borderRadius: 1.8,
            color: '#eafaf3',
            '&.Mui-selected': {
              background: 'linear-gradient(130deg, rgba(219, 174, 77, 0.9), rgba(205, 145, 24, 0.9))',
              color: '#103625',
            },
            '&.Mui-selected:hover': {
              background: 'linear-gradient(130deg, rgba(219, 174, 77, 0.95), rgba(205, 145, 24, 0.95))',
            },
            '&:hover': {
              background: 'rgba(255,255,255,0.11)',
            },
          }}
        >
          <ListItemIcon sx={{ color: activePage === item.key ? '#103625' : 'rgba(234,250,243,0.92)', minWidth: 36 }}>{item.icon}</ListItemIcon>
          <ListItemText primary={item.label} />
        </ListItemButton>
      ))}
    </List>
  </Drawer>
);

export default SidebarMui;