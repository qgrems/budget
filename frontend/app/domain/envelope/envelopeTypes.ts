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
