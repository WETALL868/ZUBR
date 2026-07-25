export type GateTypeSlug =
  | "otkatnye-vorota"
  | "sektsionnye-vorota"
  | "raspashnye-vorota"
  | "avtomatika"
  | "garazhnye-vorota"
  | "promyshlennye-vorota";

export interface GateType {
  slug: GateTypeSlug;
  href: string;
  title: string;
  shortDescription: string;
  image: string;
  imageAlt: string;
  featured?: boolean;
  isLandingPage?: boolean;
}

export interface Advantage {
  title: string;
  description: string;
}

export interface Stage {
  number: number;
  title: string;
  description: string;
}

export interface FaqItem {
  question: string;
  answer: string;
}

export type PortfolioCategory =
  | "otkatnye"
  | "sektsionnye"
  | "raspashnye"
  | "avtomatika"
  | "remont";

export interface PortfolioItem {
  id: string;
  category: PortfolioCategory;
  categoryLabel: string;
  title: string;
  district: string;
  size: string;
  automation: string;
  works: string;
  images: string[];
  beforeAfter?: {
    before: string;
    after: string;
  };
}

export interface Testimonial {
  id: string;
  name: string;
  district: string;
  workType: string;
  text: string;
  image: string;
  sourceUrl?: string;
  isDemo: true;
}

export interface FillOption {
  title: string;
  description: string;
  image: string;
}

export interface ServiceArea {
  name: string;
}

export interface Service {
  slug: string;
  title: string;
  description: string;
}
