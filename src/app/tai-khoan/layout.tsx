import { pageUser } from '@/lib/auth';
import { AccountNav } from '@/components/account';
export const metadata = { title: 'Tài khoản' };
export default async function Layout({ children }: { children: React.ReactNode }) {
  const user = await pageUser();
  return (
    <div className="container">
      <div className="page-heading" style={{ paddingTop: 40 }}>
        <span className="eyebrow">YOUR PERSONAL SPACE</span>
        <h1>Xin chào, {user.name}</h1>
        <p>Mọi thông tin mua sắm của bạn ở cùng một nơi.</p>
      </div>
      <div className="account-grid">
        <AccountNav />
        <div>{children}</div>
      </div>
    </div>
  );
}
