import { Platform } from 'react-native';
import Constants from 'expo-constants';

// Simplified API URL function that uses 127.0.0.1 for most cases, 
// with special handling only for Android emulators
export const getApiBaseUrl = () => {
  if (Platform.OS === 'android' && !__DEV__) {
    // Android emulator needs 10.0.2.2 to access the host machine
    return 'http://10.0.2.2:8000/api';
  }

  // Default to 127.0.0.1 for all other cases (web, iOS simulator)
  return 'http://127.0.0.1:8000/api';
};

// Export the API URL for use in components
export const API_URL = getApiBaseUrl();
console.log('Config initialized with API URL:', API_URL);