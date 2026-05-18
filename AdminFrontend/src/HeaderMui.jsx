import React from 'react';
import { AppBar, Toolbar, Typography, Box, IconButton, Button, Avatar, Menu, MenuItem } from '@mui/material';
import MenuIcon from '@mui/icons-material/Menu';
import LogoutIcon from '@mui/icons-material/Logout';
import AddIcon from '@mui/icons-material/Add';

const HeaderMui = ({
  currentUser,
  currentPageTitle,
  onLogout,
  isLoggingOut,
  onPrimaryAction,
  primaryActionLabel,
}) => {
  const [anchorEl, setAnchorEl] = React.useState(null);
  const handleMenu = (event) => setAnchorEl(event.currentTarget);
  const handleClose = () => setAnchorEl(null);

  return (
    <AppBar position="fixed" color="default" elevation={1} sx={{ zIndex: (theme) => theme.zIndex.drawer + 1 }}>
      <Toolbar>
        <IconButton color="inherit" edge="start" sx={{ mr: 2 }}>
          <MenuIcon />
        </IconButton>
        <Typography variant="h6" noWrap sx={{ flexGrow: 1, fontWeight: 700 }}>
          {currentPageTitle || 'ZuriTours SaaS Admin'}
        </Typography>
        {primaryActionLabel && (
          <Button
            variant="contained"
            color="primary"
            startIcon={<AddIcon />}
            onClick={onPrimaryAction}
            sx={{ mr: 2 }}
          >
            {primaryActionLabel}
          </Button>
        )}
        <Box>
          <IconButton onClick={handleMenu} color="inherit">
            <Avatar sx={{ bgcolor: '#1976d2', width: 32, height: 32 }}>
              {currentUser?.name ? currentUser.name[0] : '?'}
            </Avatar>
          </IconButton>
          <Menu anchorEl={anchorEl} open={Boolean(anchorEl)} onClose={handleClose}>
            <MenuItem disabled>{currentUser?.name || 'User'}</MenuItem>
            <MenuItem onClick={onLogout} disabled={isLoggingOut}>
              <LogoutIcon fontSize="small" sx={{ mr: 1 }} />
              {isLoggingOut ? 'Logging out...' : 'Logout'}
            </MenuItem>
          </Menu>
        </Box>
      </Toolbar>
    </AppBar>
  );
};

export default HeaderMui;