import { React, Dispatch, SetStateAction } from "react";

export interface Envelope {
  uuid: string
  updatedAt: string
  currentAmount: string
  targetedAmount: string
  name: string
  userUuid: string
  createdAt: string
  deleted: boolean
}
export interface EnvelopesData {
  envelopes: Envelope[];
  totalItems: number;
}
export interface EnvelopeState {
  envelopesData: {
    envelopes: Envelope[]
    totalItems: number
  } | null
  loading: boolean
  errorEnvelope: string | null
}

export interface EnvelopeDetails {
  envelope: {
    uuid: string;
    currentAmount: string;
    targetedAmount: string;
    name: string;
  };
  ledger: Array<{
    created_at: string;
    monetary_amount: string;
    entry_type: 'credit' | 'debit';
  }>;
}
export interface EditingNameInterface {
  id: string; name: string
}
export type UpdateEnvelopeName = (envelopeId: string, name: string, setError: Dispatch<SetStateAction<string | null>>) => Promise<void>;

