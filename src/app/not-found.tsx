import Link from 'next/link';
import { Button } from '@/components/ui/button';
export default function NotFound() {
  return (
    <div className="container section empty">
      <span className="eyebrow">404 / NOT FOUND</span>
      <h1 style={{ fontSize: 34, marginTop: 20 }}>Không tìm thấy trang</h1>
      <p>Liên kết có thể đã thay đổi hoặc bạn không có quyền truy cập.</p>
      <Button asChild>
        <Link href="/">Về trang chủ</Link>
      </Button>
    </div>
  );
}
