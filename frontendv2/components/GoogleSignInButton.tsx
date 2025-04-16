import React, { useState } from 'react';
import { TouchableOpacity, Text, StyleSheet, ActivityIndicator, View, Platform } from 'react-native';
import * as WebBrowser from 'expo-web-browser';
import { useAuth } from '../contexts/AuthContext';
import { API_URL } from '../config';

// Initialize WebBrowser for OAuth flow
WebBrowser.maybeCompleteAuthSession();

export default function GoogleSignInButton() {
  const [isLoading, setIsLoading] = useState(false);
  const { loginWithGoogle } = useAuth();

  const handleGoogleSignIn = async () => {
    setIsLoading(true);
    try {
      if (Platform.OS === 'web') {
        // For web, directly redirect to the backend OAuth endpoint
        window.location.href = `${API_URL}/connect/google?platform=web`;
      } else {
        // For mobile, open the backend OAuth endpoint in the browser
        const result = await WebBrowser.openAuthSessionAsync(
          `${API_URL}/connect/google?platform=mobile`,
          'http://localhost:8081/oauth/google/callback'
        );

        if (result.type === 'success') {
          // Extract tokens from the URL parameters
          const url = result.url;
          const params = new URLSearchParams(url.split('?')[1]);
          
          const email = params.get('email');
          const token = params.get('token');
          const refreshToken = params.get('refresh_token');
          
          if (email && token) {
            await loginWithGoogle(email, token, refreshToken || undefined);
          }
        }
      }
    } catch (error) {
      console.error('Google Sign-in error:', error);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <TouchableOpacity
      style={styles.button}
      onPress={handleGoogleSignIn}
      disabled={isLoading}
    >
      {isLoading ? (
        <ActivityIndicator color="#fff" size="small" />
      ) : (
        <View style={styles.buttonContent}>
          <View style={styles.googleIcon}>
            <Text style={styles.googleIconText}>G</Text>
          </View>
          <Text style={styles.buttonText}>Sign in with Google</Text>
        </View>
      )}
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  button: {
    backgroundColor: '#4285F4',
    padding: 12,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
    marginVertical: 10,
  },
  buttonContent: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
  },
  googleIcon: {
    width: 24,
    height: 24,
    backgroundColor: 'white',
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 8,
  },
  googleIconText: {
    color: '#4285F4',
    fontWeight: 'bold',
  },
  buttonText: {
    color: 'white',
    fontWeight: 'bold',
    fontSize: 16,
  },
});