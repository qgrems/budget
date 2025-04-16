import { Slot, Stack } from 'expo-router';
import { useEffect } from 'react';
import { Platform } from 'react-native';
import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';
import { AuthProvider } from '../contexts/AuthContext';
import { SocketProvider } from '../contexts/SocketContext';
import { ErrorProvider } from '../contexts/ErrorContext';

// Configure linking for deep links
const linking = {
  prefixes: [
    // Add the custom scheme for your app
    'budget://', 
    // Add your website domain if you have one
    'https://your-domain.com',
    'http://localhost:8081'
  ],
  config: {
    initialRouteName: 'index',
    screens: {
      index: '',
      signin: 'signin',
      signup: 'signup',
      envelopes: 'envelopes',
      'oauth/google/callback': 'oauth/google/callback',
    },
  },
};

export default function RootLayout() {
  useEffect(() => {
    // Add any root-level initialization here
    console.log(`App running on ${Platform.OS}`);
    
    // Set up deep link handling
    const handleDeepLink = async (event: Linking.EventType) => {
      console.log('Deep link received:', event.url);
    };

    // Listen for incoming links
    const subscription = Linking.addEventListener('url', handleDeepLink);

    // Check for initial URL that may have launched the app
    const checkInitialUrl = async () => {
      const initialUrl = await Linking.getInitialURL();
      if (initialUrl) {
        console.log('App opened with initial URL:', initialUrl);
      }
    };

    checkInitialUrl();
    
    // Cleanup the subscription when unmounting
    return () => {
      subscription.remove();
    };
  }, []);

  return (
    <ErrorProvider>
      <AuthProvider>
        <SocketProvider>
          <Stack 
            screenOptions={{
              headerShown: false,
            }}
            // Apply deep linking configuration
            linking={linking}
          />
        </SocketProvider>
      </AuthProvider>
    </ErrorProvider>
  );
}