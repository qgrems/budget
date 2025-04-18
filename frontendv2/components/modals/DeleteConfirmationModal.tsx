import React from 'react';
import { View, Text, Modal, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import ActionButton from '@/components/buttons/ActionButton';

interface DeleteConfirmationModalProps {
  visible: boolean;
  onClose: () => void;
  onConfirm: () => void;
  name: string;
}

const DeleteConfirmationModal: React.FC<DeleteConfirmationModalProps> = ({
  visible,
  onClose,
  onConfirm,
  name
}) => {
  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
    >
      <View className="flex-1 justify-center items-center bg-black/50">
        <View className="w-[90%] max-w-md bg-background-light p-6 rounded-2xl shadow-lg">
          <View className="flex-row justify-between items-center mb-5">
            <View className="flex-row items-center">
              <View className="w-8 h-8 rounded-full bg-danger-100 items-center justify-center mr-2">
                <Ionicons name="trash-outline" size={18} color="#dc2626" />
              </View>
              <Text className="text-xl font-bold text-text-primary">
                Delete Envelope
              </Text>
            </View>
            <TouchableOpacity
              onPress={onClose}
              className="w-8 h-8 rounded-full bg-surface-subtle items-center justify-center"
              hitSlop={{ top: 10, right: 10, bottom: 10, left: 10 }}
            >
              <Ionicons name="close" size={20} color="#64748b" />
            </TouchableOpacity>
          </View>

          <View className="bg-danger-50 p-4 rounded-xl mb-5 border border-danger-200">
            <Text className="text-danger-800">
              Are you sure you want to delete the envelope "{name}"? This action cannot be undone.
            </Text>
          </View>

          <View className="flex-row space-x-3 mt-2">
            <ActionButton
              label="Cancel"
              onPress={onClose} 
              className="flex-1"
            />
            <ActionButton
              label="Delete"
              onPress={onConfirm}
              className="flex-1"
            />
          </View>
        </View>
      </View>
    </Modal>
  );
};

export default DeleteConfirmationModal;