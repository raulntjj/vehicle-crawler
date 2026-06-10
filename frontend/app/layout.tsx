import type { Metadata } from "next";
import { Inter, Geist_Mono } from "next/font/google";
import { QueryProvider } from "@/lib/query-provider";
import "./globals.css";

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "Catálogo de Veículos | Encontre seu próximo carro",
  description:
    "Pesquise e compare veículos de múltiplos portais. Filtros avançados por marca, preço, quilometragem e ano. Acompanhe o histórico de preços.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="pt-BR"
      className={`${inter.variable} ${geistMono.variable} antialiased`}
    >
      <body className="min-h-dvh bg-background text-foreground">
        <QueryProvider>{children}</QueryProvider>
      </body>
    </html>
  );
}
