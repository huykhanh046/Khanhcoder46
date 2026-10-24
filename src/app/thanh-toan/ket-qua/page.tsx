import Link from 'next/link';
import { Button } from '@/components/ui/button';
export default function Page() {
  return (
    <div className="container section empty">
      <span className="eyebrow">PAYMENT RETURN</span>
      <h1 style={{ fontSize: 32, marginTop: 20 }}>Đã trở về từ cổng thanh toán</h1>
      <p>
        Trạng thái thanh toán được cập nhật sau khi server xác minh thông báo VNPay.
        <br />
        Bạn có thể xem trạng thái chính thức trong chi tiết đơn hàng.
      </p>
      <Button asChild>
        <Link href="/tai-khoan/don-hang">Kiểm tra đơn hàng</Link>
      </Button>
    </div>
  );
}
