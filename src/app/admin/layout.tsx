import { pageUser } from '@/lib/auth';
import { AdminNav } from '@/components/admin-nav';
export const metadata = { title: 'Quản trị' };
export default async function Layout({ children }: { children: React.ReactNode }) {
  await pageUser(true);
  return (
    <div className="admin-shell">
      <div className="container">
        <div className="page-heading" style={{ paddingTop: 35 }}>
          <span className="eyebrow">KH FASHION / BACK OFFICE</span>
          <h1 style={{ fontSize: 34 }}>Quản trị cửa hàng</h1>
        </div>
        <div className="admin-layout">
          <AdminNav />
          <div className="admin-content">{children}</div>
        </div>
      </div>
    </div>
  );
}
