export interface Widget {
    tag?: string
    widgetId: string
    applicationType: string
    widgetLocation?: {
        id: string
        name: string
    }
    extensions?: {
        product_star: {
            tag: string
        }
    }
    attributes?: {
        [key: string]: { value?: string; attributeName?: string }
    }
}