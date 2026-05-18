import { createTheme } from '@mui/material/styles';

const theme = createTheme({
  palette: {
    mode: 'light',
    primary: {
      main: '#006b42',
      dark: '#0f3a28',
      light: '#1a6645',
      contrastText: '#ffffff',
    },
    secondary: {
      main: '#d3a340',
      dark: '#b8862b',
      light: '#e4bf6a',
      contrastText: '#103625',
    },
    background: {
      default: '#f4f8f5',
      paper: '#ffffff',
    },
    text: {
      primary: '#113f2c',
      secondary: '#466958',
    },
    divider: '#dbe8df',
  },
  shape: {
    borderRadius: 14,
  },
  components: {
    MuiCssBaseline: {
      styleOverrides: {
        body: {
          backgroundColor: '#f4f8f5',
        },
      },
    },
    MuiPaper: {
      styleOverrides: {
        root: {
          backgroundImage: 'none',
        },
      },
    },
    MuiButton: {
      styleOverrides: {
        root: {
          fontWeight: 700,
          textTransform: 'none',
        },
        containedPrimary: {
          background: 'linear-gradient(120deg, #0f3a28, #1a6645)',
        },
        containedSecondary: {
          background: 'linear-gradient(120deg, #f0c461, #d3a340)',
          color: '#103625',
        },
      },
    },
    MuiInputBase: {
      styleOverrides: {
        root: {
          background: '#ffffff',
          color: '#113f2c',
          borderRadius: 8,
        },
        input: {
          color: '#113f2c',
        },
      },
    },
    MuiSelect: {
      styleOverrides: {
        select: {
          background: '#ffffff',
          color: '#113f2c',
        },
      },
    },
  },
});

export default theme;