/**
 * Types partagés pour les réponses API catalogue.
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

export type BookAvailabilityStatus = "available" | "preorder" | "coming";

export interface PreorderCampaign {
  goal?: number | null;
  reserved?: number;
  bonuses?: string[];
}

export interface BookSummary {
  id: string;
  title: string;
  slug: string;
  description?: string;
  subtitle?: string;
  tagline?: string;
  coverImage?: string | null;
  accentColor?: string;
  isFeatured: boolean;
  pageCount?: number;
  readingTime?: string;
  language?: string;
  releaseDate?: string;
  availabilityStatus?: BookAvailabilityStatus;
  availabilityLabel?: string;
  preorderCampaign?: PreorderCampaign | null;
  author?: AuthorSummary;
  formats?: BookFormat[];
}

export interface ExcerptPage {
  kind: "cover" | "chapter" | "text";
  chapter?: string;
  title?: string;
  paragraphs?: string[];
  pageLabel?: string;
}

export interface BookReviewStats {
  count: number;
  averageRating: number | null;
}

export interface BookReview {
  id: string;
  authorName: string;
  authorRole?: string | null;
  rating: number;
  content: string;
  createdAt?: string;
}

export interface BookDetail extends BookSummary {
  authorNote?: string;
  publishedAt?: string;
  category?: string;
  themes?: string[];
  aboutParagraphs?: string[];
  excerpt?: ExcerptPage[];
  reviewStats?: BookReviewStats;
  reviews?: BookReview[];
  relatedBooks?: BookSummary[];
}

export interface AuthorDetail extends AuthorSummary {
  fullBio?: string;
  coverImage?: string | null;
  socialLinks?: Record<string, string>;
  featuredQuote?: string;
  books?: BookSummary[];
}
