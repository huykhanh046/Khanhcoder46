import type { Metadata } from 'next';
import './globals.css';
import { currentUser } from '@/lib/auth';
import { CartProvider } from '@/components/cart-provider';
import { Header } from '@/components/header';
import { Footer } from '@/components/footer';
export const metadata: Metadata = {
  title: { default: 'KH Fashion — Phong cách của riêng bạn', template: '%s | KH Fashion' },
  description: 'Thời trang tối giản cho cuộc sống mỗi ngày. Khám phá bộ sưu tập KH Fashion.',
};
export const dynamic = 'force-dynamic';
export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const user = await currentUser();
  return (
    <html lang="vi">
      <body>
        <CartProvider>
          <a href="#main" className="skip-link">
            Đến nội dung chính
          </a>
          <Header user={user} />
          <main id="main">{children}</main>
          <Footer />
        </CartProvider>
      </body>
    </html>
  );
}
