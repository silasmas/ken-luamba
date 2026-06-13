import { BookCard } from "@/components/shop/BookCard";
import { fetchBooks } from "@/lib/api/books";

export const dynamic = "force-dynamic";

/**
 * Page catalogue des livres publiés.
 */
export default async function LivresPage() {
  const response = await fetchBooks();

  return (
    <div>
      <div className="mb-10">
        <h1 className="text-3xl font-bold text-stone-900">Les livres</h1>
        <p className="mt-3 text-stone-600">
          Pré-commandez et achetez les ouvrages du pasteur Ken Luamba.
        </p>
      </div>
      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {response.data.map((book) => (
          <BookCard key={book.id} book={book} />
        ))}
      </div>
    </div>
  );
}
