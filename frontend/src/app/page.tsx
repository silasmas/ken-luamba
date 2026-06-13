import { fetchBooks, fetchAuthor } from "@/lib/api/books";
import { HeroHomeSection } from "@/components/sections/HeroHomeSection";
import { ManifestoSection } from "@/components/sections/ManifestoSection";
import { FeaturedBookSection } from "@/components/sections/FeaturedBookSection";
import { LaunchCampaignSection } from "@/components/sections/LaunchCampaignSection";
import { LibraryHomeSection } from "@/components/sections/LibraryHomeSection";
import { AuthorPreviewSection } from "@/components/sections/AuthorPreviewSection";

export const dynamic = "force-dynamic";

/**
 * Page d'accueil — maquette book-site branchée sur l'API catalogue et auteur.
 */
export default async function Home() {
  const [featuredResponse, allResponse, authorResponse] = await Promise.all([
    fetchBooks({ featured: true }),
    fetchBooks(),
    fetchAuthor("ken-luamba").catch(() => null),
  ]);

  const author = authorResponse?.data ?? null;

  const featuredBooks =
    featuredResponse.data.length > 0
      ? featuredResponse.data
      : allResponse.data.slice(0, 3);

  const heroBook = featuredBooks[0] ?? allResponse.data[0] ?? null;
  const libraryBooks = allResponse.data.slice(0, 3);

  return (
    <>
      <HeroHomeSection featuredBook={heroBook} author={author} />
      <ManifestoSection />
      {heroBook && <FeaturedBookSection book={heroBook} />}
      {heroBook && <LaunchCampaignSection book={heroBook} />}
      {libraryBooks.length > 0 && <LibraryHomeSection books={libraryBooks} />}
      <AuthorPreviewSection author={author} />
    </>
  );
}
