/**
 * Types partagés pour les réponses API.
 */
export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface CurrentPrice {
  periodId: string;
  label: string;
  type: string;
  typeLabel: string;
  price: string;
  currency: string;
  startAt: string;
  endAt: string;
}

export interface AuthorSummary {
  id: string;
  fullName: string;
  slug: string;
  title?: string;
  shortBio?: string;
  profileImage?: string | null;
}

export interface DigitalLimits {
  fileTypeLabel?: string | null;
  streamExpiryHours: number;
  maxDownloads: number;
  personalAccess: boolean;
  noSharing: boolean;
  summary: string;
}

export interface BookFormat {
  id: string;
  type: string;
  typeLabel: string;
  sku: string;
  isDigital: boolean;
  digitalFileType?: string | null;
  digitalFileTypeLabel?: string | null;
  digitalLimits?: DigitalLimits | null;
  stockQuantity?: number | null;
  currentPrice: CurrentPrice | null;
}

export interface BookSummary {
  id: string;
  title: string;
  slug: string;
  description?: string;
  coverImage?: string | null;
  isFeatured: boolean;
  author?: AuthorSummary;
  formats?: BookFormat[];
}

export interface BookDetail extends BookSummary {
  authorNote?: string;
  publishedAt?: string;
}

export interface AuthorDetail extends AuthorSummary {
  fullBio?: string;
  coverImage?: string | null;
  socialLinks?: Record<string, string>;
  featuredQuote?: string;
  books?: BookSummary[];
}
