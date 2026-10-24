'use client';
import { Button } from '@/components/ui/button';
export default function ErrorPage({ reset }: { reset: () => void }) {
  return (
    <div className="container section empty">
      <h1 style={{ fontSize: 32 }}>Chưa thể tải nội dung</h1>
      <p>Vui lòng thử lại. Nếu lỗi tiếp diễn, kiểm tra kết nối cơ sở dữ liệu.</p>
      <Button onClick={reset}>Thử lại</Button>
    </div>
  );
}
