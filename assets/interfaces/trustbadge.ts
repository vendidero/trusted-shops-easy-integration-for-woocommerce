import type { TrustbadgeChild } from './trustbadge-child'

export interface Trustbadge {
    id: string,
    salesChannelRef: string,
    children: TrustbadgeChild[],
}