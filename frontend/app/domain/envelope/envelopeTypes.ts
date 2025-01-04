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
