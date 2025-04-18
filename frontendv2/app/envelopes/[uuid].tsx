import React, { useState, useEffect, useCallback, useRef } from 'react';
import { 
  View, 
  Text, 
  ScrollView, 
  TouchableOpacity, 
  ActivityIndicator,
  TextInput,
  RefreshControl
} from 'react-native';
import { useLocalSearchParams, Stack, useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { StatusBar } from 'expo-status-bar';
import { useEnvelopes } from '@/contexts/EnvelopeContext';
import { envelopeService, EnvelopeDetails } from '@/services/envelopeService';
import { useErrorContext } from '@/contexts/ErrorContext';
import { formatCurrency, getCurrencySymbol } from '@/utils/currencyUtils';
import EnvelopePieChart from '@/components/EnvelopePieChart';
import formatAmount from '@/utils/formatAmount';
import validateAmount from '@/utils/validateAmount';
import DescriptionModal from '@/components/modals/DescriptionModal';
import DeleteConfirmationModal from '@/components/modals/DeleteConfirmationModal';
import { useSocket } from '@/contexts/SocketContext';

export default function EnvelopeDetailScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const { setError } = useErrorContext();
  const { 
    deleteEnvelope, 
    creditEnvelope, 
    debitEnvelope, 
    updateEnvelopeName, 
    updateTargetBudget,
    fetchEnvelopeDetails, 
    listenToEnvelopeUpdates
  } = useEnvelopes();
  const { connected } = useSocket();
  
  // Access uuid directly from params
  const uuid = params.uuid as string;

  // Use a ref to track the latest envelope data to prevent stale closures in callbacks
  const latestEnvelopeRef = useRef<EnvelopeDetails | null>(null);
  const [details, setDetails] = useState<EnvelopeDetails | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [editingName, setEditingName] = useState<{ name: string } | null>(null);
  const [editingTarget, setEditingTarget] = useState<{ amount: string } | null>(null);
  const [amount, setAmount] = useState('');
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [descriptionModalOpen, setDescriptionModalOpen] = useState(false);
  const [currentAction, setCurrentAction] = useState<{ type: 'credit' | 'debit', amount: string } | null>(null);
  const [pendingAction, setPendingAction] = useState(false);
  const [socketUpdated, setSocketUpdated] = useState(false);
  // Add a forceUpdate counter to trigger re-renders
  const [updateCounter, setUpdateCounter] = useState(0);

  // Reference to track if component is mounted
  const isMounted = useRef(true);

  // Keep the latest details ref updated
  useEffect(() => {
    latestEnvelopeRef.current = details;
  }, [details]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      isMounted.current = false;
    };
  }, []);

  // Initial load of envelope details
  useEffect(() => {
    loadEnvelopeDetails();
  }, [uuid]);

  // Function to load envelope details
  const loadEnvelopeDetails = async () => {
    if (!uuid) return;
    
    try {
      setLoading(true);
      const response = await fetchEnvelopeDetails(uuid);
      if (isMounted.current && response) {
        setDetails(response);
        // Also increment update counter to ensure UI refresh
        setUpdateCounter(prev => prev + 1);
      }
    } catch (err) {
      console.error('Failed to fetch envelope details:', err);
      setError('Failed to load envelope details');
    } finally {
      if (isMounted.current) {
        setLoading(false);
      }
    }
  };

  // Setup envelope-specific WebSocket event handling
  useEffect(() => {
    if (!uuid) return;

    // Set up event listeners for this specific envelope
    const cleanup = listenToEnvelopeUpdates(
      uuid,
      // Update callback
      () => {
        console.log('Envelope update received from WebSocket');
        if (isMounted.current) {
          setSocketUpdated(true);
          // Force reload with full refresh strategy
          loadEnvelopeDetails();
        }
      },
      // Delete callback
      () => {
        console.log('Envelope deleted notification received');
        if (isMounted.current) {
          setError('This envelope has been deleted');
          router.back();
        }
      }
    );

    // Return cleanup function
    return cleanup;
  }, [uuid, listenToEnvelopeUpdates, router]);

  // Only refresh data when socket connection changes from disconnected to connected
  useEffect(() => {
    if (connected && details) {
      console.log('Socket reconnected, refreshing envelope details');
      loadEnvelopeDetails();
    }
  }, [connected]);
  
  // Visual feedback for socket updates with debounce
  useEffect(() => {
    if (socketUpdated) {
      const timer = setTimeout(() => {
        if (isMounted.current) {
          setSocketUpdated(false);
        }
      }, 2000);
      
      return () => clearTimeout(timer);
    }
  }, [socketUpdated]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await loadEnvelopeDetails();
    setRefreshing(false);
  }, []);

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' });
  };

  const handleNameChange = (newName: string) => {
    setEditingName({ name: newName });
  };

  const handleUpdateName = async () => {
    if (!details || !editingName) return;
    
    const newName = editingName.name.trim();
    
    if (newName === details.envelope.name) {
      setEditingName(null);
      return;
    }
    
    if (newName.length > 25) {
      setError('Name cannot exceed 25 characters');
      return;
    }
    
    if (newName === '') {
      setError('Name cannot be empty');
      return;
    }
    
    setPendingAction(true);
    try {
      await updateEnvelopeName(details.envelope.uuid, newName, setError);
      // Force refresh after operation completes
      await loadEnvelopeDetails();
    } catch (err) {
      console.error('Failed to update name:', err);
    } finally {
      setEditingName(null);
      setPendingAction(false);
    }
  };

  const handleTargetChange = (newTarget: string) => {
    setEditingTarget({ amount: newTarget });
  };

  const handleUpdateTarget = async () => {
    if (!details || !editingTarget) return;
    
    const newTarget = formatAmount(editingTarget.amount);
    const currentAmount = details.envelope.currentAmount;
    
    if (Number(newTarget) === Number(details.envelope.targetedAmount)) {
      setEditingTarget(null);
      return;
    }
    
    if (Number(newTarget) < Number(currentAmount)) {
      setError('Target amount cannot be less than current amount');
      return;
    }
    
    setPendingAction(true);
    try {
      await updateTargetBudget(details.envelope.uuid, newTarget, currentAmount, setError);
      // Force refresh after operation completes
      await loadEnvelopeDetails();
    } catch (err) {
      console.error('Failed to update target budget:', err);
    } finally {
      setEditingTarget(null);
      setPendingAction(false);
    }
  };

  const handleCredit = () => {
    if (!amount || !validateAmount(amount)) {
      setError('Please enter a valid amount');
      return;
    }
    
    const processedAmount = formatAmount(amount);
    
    if (Number(processedAmount) <= 0) {
      setError('Amount must be greater than zero');
      return;
    }
    
    setCurrentAction({
      type: 'credit',
      amount: processedAmount,
    });
    
    setDescriptionModalOpen(true);
  };

  const handleDebit = () => {
    if (!details) return;
    
    if (!amount || !validateAmount(amount)) {
      setError('Please enter a valid amount');
      return;
    }
    
    const processedAmount = formatAmount(amount);
    
    if (Number(processedAmount) <= 0) {
      setError('Amount must be greater than zero');
      return;
    }
    
    if (Number(processedAmount) > Number(details.envelope.currentAmount)) {
      setError('Cannot debit more than current amount');
      return;
    }
    
    setCurrentAction({
      type: 'debit',
      amount: processedAmount,
    });
    
    setDescriptionModalOpen(true);
  };

  const handleDeleteEnvelope = async () => {
    if (!details) return;
    
    setPendingAction(true);
    try {
      await deleteEnvelope(details.envelope.uuid, setError);
      router.back();
    } catch (err) {
      console.error('Failed to delete envelope:', err);
    } finally {
      setPendingAction(false);
      setDeleteModalOpen(false);
    }
  };

  const handleDescriptionSubmit = async (description: string) => {
    if (!details || !currentAction) return;
    
    setPendingAction(true);
    try {
      if (currentAction.type === 'credit') {
        await creditEnvelope(
          details.envelope.uuid, 
          currentAction.amount, 
          description, 
          setError
        );
      } else {
        await debitEnvelope(
          details.envelope.uuid, 
          currentAction.amount, 
          description, 
          setError
        );
      }
      setAmount('');
      // Force refresh after operation completes
      await loadEnvelopeDetails();
    } catch (err) {
      console.error('Failed to process transaction:', err);
    } finally {
      setPendingAction(false);
      setDescriptionModalOpen(false);
      setCurrentAction(null);
    }
  };

  if (loading && !details) {
    return (
      <View className="flex-1 justify-center items-center bg-white">
        <ActivityIndicator size="large" color="#6366f1" />
      </View>
    );
  }

  if (!details) {
    return (
      <View className="flex-1 justify-center items-center bg-white">
        <Text className="text-gray-600 text-lg">Envelope not found</Text>
        <TouchableOpacity 
          className="mt-4 px-4 py-2 bg-indigo-600 rounded-md"
          onPress={() => router.back()}
        >
          <Text className="text-white">Go Back</Text>
        </TouchableOpacity>
      </View>
    );
  }

  const progress = Number(details.envelope.currentAmount) / Number(details.envelope.targetedAmount);

  return (
    <View className="flex-1 bg-background-subtle">
      <StatusBar style="light" />
      
      <Stack.Screen 
        options={{
          title: details.envelope.name,
          headerStyle: {
            backgroundColor: '#0c6cf2', // primary-600
          },
          headerTintColor: '#fff',
          headerTitleStyle: {
            fontWeight: 'bold',
          },
          headerRight: () => (
            <TouchableOpacity 
              onPress={() => setDeleteModalOpen(true)}
              className="mr-4"
            >
              <Ionicons name="trash-outline" size={24} color="#fff" />
            </TouchableOpacity>
          ),
        }} 
      />
      
      {/* Header Section - Similar to homepage */}
      <View className="bg-primary-600 px-6 pt-6 pb-12 rounded-b-3xl shadow-lg">
        <View className="flex-row items-center">
          {editingName ? (
            <View className="flex-row items-center flex-1">
              <TextInput
                value={editingName.name}
                onChangeText={handleNameChange}
                className="flex-1 bg-white/20 px-3 py-2 rounded-lg text-white text-lg font-semibold"
                maxLength={25}
                autoFocus
                placeholderTextColor="#e0e7ff"
              />
              <TouchableOpacity 
                onPress={handleUpdateName}
                className="ml-2 p-2 bg-white/20 rounded-full"
                disabled={pendingAction}
              >
                <Ionicons name="checkmark" size={20} color="#ffffff" />
              </TouchableOpacity>
              <TouchableOpacity 
                onPress={() => setEditingName(null)}
                className="ml-2 p-2 bg-white/20 rounded-full"
                disabled={pendingAction}
              >
                <Ionicons name="close" size={20} color="#ffffff" />
              </TouchableOpacity>
            </View>
          ) : (
            <TouchableOpacity 
              onPress={() => setEditingName({ name: details.envelope.name })}
              className="flex-row items-center flex-1"
              disabled={pendingAction || details.envelope.pending}
            >
              <Text className="text-3xl font-bold text-white mr-2">{details.envelope.name}</Text>
              <Ionicons name="create-outline" size={20} color="white" />
            </TouchableOpacity>
          )}
        </View>
        
        <View className="flex-row justify-between items-center mt-4">
          <View>
            <Text className="text-primary-100">Current Balance</Text>
            <Text className="text-3xl font-bold text-white">
              {formatCurrency(details.envelope.currentAmount, details.envelope.currency)}
            </Text>
            
            <View className="flex-row items-center mt-2">
              <Text className="text-primary-100">
                of {' '}
                {editingTarget ? (
                  <View className="inline-flex flex-row items-center bg-white/20 rounded-lg px-2 py-1">
                    <Text className="text-white">
                      {getCurrencySymbol(details.envelope.currency)}
                    </Text>
                    <TextInput
                      value={editingTarget.amount}
                      onChangeText={handleTargetChange}
                      className="text-white w-20"
                      keyboardType="numeric"
                      maxLength={10}
                      autoFocus
                    />
                    <TouchableOpacity 
                      onPress={handleUpdateTarget}
                      className="ml-1 p-1 bg-white/20 rounded-full"
                      disabled={pendingAction}
                    >
                      <Ionicons name="checkmark" size={14} color="#ffffff" />
                    </TouchableOpacity>
                    <TouchableOpacity 
                      onPress={() => setEditingTarget(null)}
                      className="ml-1 p-1 bg-white/20 rounded-full"
                      disabled={pendingAction}
                    >
                      <Ionicons name="close" size={14} color="#ffffff" />
                    </TouchableOpacity>
                  </View>
                ) : (
                  <TouchableOpacity 
                    onPress={() => setEditingTarget({ amount: details.envelope.targetedAmount })}
                    className="inline-flex flex-row items-center"
                    disabled={pendingAction || details.envelope.pending}
                  >
                    <Text className="text-white font-medium">
                      {formatCurrency(details.envelope.targetedAmount, details.envelope.currency)}
                    </Text>
                    <Ionicons name="create-outline" size={14} color="white" className="ml-1" />
                  </TouchableOpacity>
                )}
                {' '}goal
              </Text>
              
              {progress >= 1 ? (
                <View className="bg-success-50 px-2 py-1 rounded-full ml-2">
                  <Text className="text-success-700 text-xs font-medium">Completed</Text>
                </View>
              ) : (
                <View className="bg-white/20 px-2 py-1 rounded-full ml-2">
                  <Text className="text-white text-xs font-medium">{Math.round(progress * 100)}% Filled</Text>
                </View>
              )}
            </View>
          </View>
          
          <View className="w-24 h-24">
            <EnvelopePieChart 
              currentAmount={Number(details.envelope.currentAmount)}
              targetedAmount={Number(details.envelope.targetedAmount)}
            />
          </View>
        </View>
      </View>
      
      <ScrollView
        className="flex-1"
        contentContainerStyle={{ padding: 16, paddingBottom: 80 }}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      >
        {/* Quick Actions Card */}
        <View className="card bg-white rounded-2xl shadow-md overflow-hidden mb-6">
          <View className="p-6">
            <Text className="text-lg font-semibold text-text-primary mb-4">Quick Actions</Text>
            
            <View className="flex-row space-x-3 mb-4">
              <TouchableOpacity 
                onPress={() => {
                  setAmount('10');
                  setCurrentAction({ type: 'credit', amount: '10' });
                  setDescriptionModalOpen(true);
                }}
                className="flex-1 bg-success-100 p-4 rounded-xl flex-row items-center justify-center"
              >
                <Ionicons name="arrow-down-circle-outline" size={20} color="#16a34a" />
                <Text className="text-success-700 font-medium ml-2">Add $10</Text>
              </TouchableOpacity>
              
              <TouchableOpacity 
                onPress={() => {
                  setAmount('10');
                  setCurrentAction({ type: 'debit', amount: '10' });
                  setDescriptionModalOpen(true);
                }}
                className="flex-1 bg-danger-100 p-4 rounded-xl flex-row items-center justify-center"
                disabled={Number(details.envelope.currentAmount) < 10}
              >
                <Ionicons name="arrow-up-circle-outline" size={20} color="#dc2626" />
                <Text className="text-danger-700 font-medium ml-2">Spend $10</Text>
              </TouchableOpacity>
            </View>
            
            <View className="flex-row space-x-3">
              <TouchableOpacity 
                onPress={() => {
                  setAmount('25');
                  setCurrentAction({ type: 'credit', amount: '25' });
                  setDescriptionModalOpen(true);
                }}
                className="flex-1 bg-success-100 p-4 rounded-xl flex-row items-center justify-center"
              >
                <Ionicons name="arrow-down-circle-outline" size={20} color="#16a34a" />
                <Text className="text-success-700 font-medium ml-2">Add $25</Text>
              </TouchableOpacity>
              
              <TouchableOpacity 
                onPress={() => {
                  setAmount('25');
                  setCurrentAction({ type: 'debit', amount: '25' });
                  setDescriptionModalOpen(true);
                }}
                className="flex-1 bg-danger-100 p-4 rounded-xl flex-row items-center justify-center"
                disabled={Number(details.envelope.currentAmount) < 25}
              >
                <Ionicons name="arrow-up-circle-outline" size={20} color="#dc2626" />
                <Text className="text-danger-700 font-medium ml-2">Spend $25</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
        
        {/* Custom Amount Card */}
        <View className="card bg-white rounded-2xl shadow-md overflow-hidden mb-6">
          <View className="p-6">
            <Text className="text-lg font-semibold text-text-primary mb-4">Custom Amount</Text>
            
            <TextInput
              value={amount}
              onChangeText={setAmount}
              placeholder="Enter amount"
              keyboardType="decimal-pad"
              className="border border-surface-border rounded-lg p-3 mb-3 bg-white"
            />
            
            <View className="flex-row space-x-3">
              <TouchableOpacity
                onPress={handleCredit}
                className="flex-1 bg-success-500 p-3 rounded-xl"
                disabled={
                  pendingAction || 
                  details.envelope.pending || 
                  !amount || 
                  Number(amount) <= 0
                }
              >
                <Text className="text-white text-center font-semibold">Credit</Text>
              </TouchableOpacity>
              
              <TouchableOpacity
                onPress={handleDebit}
                className="flex-1 bg-danger-500 p-3 rounded-xl"
                disabled={
                  pendingAction || 
                  details.envelope.pending || 
                  !amount || 
                  Number(amount) <= 0 ||
                  Number(amount) > Number(details.envelope.currentAmount)
                }
              >
                <Text className="text-white text-center font-semibold">Debit</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
        
        {/* Envelope Details Card */}
        <View className="card bg-white rounded-2xl shadow-md overflow-hidden mb-6">
          <View className="p-6">
            <Text className="text-lg font-semibold text-text-primary mb-4">Envelope Details</Text>
            
            <View className="space-y-4">
              <View className="flex-row justify-between">
                <Text className="text-text-secondary">Created</Text>
                <Text className="text-text-primary font-medium">
                  {formatDate(details.envelope.createdAt)}
                </Text>
              </View>
              
              <View className="flex-row justify-between">
                <Text className="text-text-secondary">Last Updated</Text>
                <Text className="text-text-primary font-medium">
                  {formatDate(details.envelope.updatedAt)}
                </Text>
              </View>
              
              <View className="flex-row justify-between">
                <Text className="text-text-secondary">Currency</Text>
                <Text className="text-text-primary font-medium">{details.envelope.currency}</Text>
              </View>
              
              <View className="flex-row justify-between">
                <Text className="text-text-secondary">Status</Text>
                {progress >= 1 ? (
                  <Text className="text-success-600 font-medium">Completed</Text>
                ) : progress > 0.5 ? (
                  <Text className="text-primary-600 font-medium">Good progress</Text>
                ) : progress > 0 ? (
                  <Text className="text-warning-600 font-medium">Getting started</Text>
                ) : (
                  <Text className="text-secondary-600 font-medium">Not started</Text>
                )}
              </View>
            </View>
          </View>
        </View>
        
        {/* Transaction History Card */}
        <View className="card bg-white rounded-2xl shadow-md overflow-hidden mb-6">
          <View className="p-6">
            <Text className="text-lg font-semibold text-text-primary mb-4">Transaction History</Text>
            
            {details.ledger.length === 0 ? (
              <View className="py-4 items-center">
                <View className="w-16 h-16 rounded-full bg-secondary-100 items-center justify-center mb-2">
                  <Ionicons name="document-text-outline" size={24} color="#64748b" />
                </View>
                <Text className="text-text-secondary text-center">No transactions yet</Text>
              </View>
            ) : (
              <View className="space-y-3">
                {details.ledger.map((transaction, index) => (
                  <View 
                    key={index}
                    className="p-3 bg-background-subtle rounded-lg flex-row justify-between items-center"
                  >
                    <View className="flex-row items-center">
                      <View className={`w-10 h-10 rounded-full items-center justify-center mr-3 ${
                        transaction.entry_type === "credit" 
                          ? "bg-success-100" 
                          : "bg-danger-100"
                      }`}>
                        <Ionicons 
                          name={transaction.entry_type === "credit" ? "arrow-down" : "arrow-up"} 
                          size={18} 
                          color={transaction.entry_type === "credit" ? "#16a34a" : "#dc2626"} 
                        />
                      </View>
                      
                      <View>
                        <Text className="font-medium text-text-primary">
                          {transaction.description || transaction.entry_type === "credit" ? "Added funds" : "Spent funds"}
                        </Text>
                        <Text className="text-sm text-text-secondary">
                          {formatDate(transaction.created_at)}
                        </Text>
                      </View>
                    </View>
                    
                    <Text
                      className={transaction.entry_type === "credit" 
                        ? "text-success-600 font-semibold" 
                        : "text-danger-600 font-semibold"
                      }
                    >
                      {transaction.entry_type === "credit" 
                        ? `+${formatCurrency(transaction.monetary_amount, details.envelope.currency)}` 
                        : `-${formatCurrency(transaction.monetary_amount, details.envelope.currency)}`
                      }
                    </Text>
                  </View>
                ))}
              </View>
            )}
          </View>
        </View>
        
        {/* Delete Envelope Button */}
        <TouchableOpacity
          onPress={() => setDeleteModalOpen(true)}
          className="bg-danger-100 rounded-xl p-4 items-center mb-10"
          disabled={pendingAction || details.envelope.pending}
        >
          <Text className="text-danger-700 font-semibold">Delete Envelope</Text>
        </TouchableOpacity>
      </ScrollView>

      <DeleteConfirmationModal
        visible={deleteModalOpen}
        onClose={() => setDeleteModalOpen(false)}
        onConfirm={handleDeleteEnvelope}
        name={details.envelope.name}
      />

      <DescriptionModal
        visible={descriptionModalOpen}
        onClose={() => setDescriptionModalOpen(false)}
        onSubmit={handleDescriptionSubmit}
        actionType={currentAction?.type || 'credit'}
      />
    </View>
  );
}