import type { Channel } from './channel'

export interface Settings {
    client_id: string;
    client_secret: string;
    channels: Channel[]
}