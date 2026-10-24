import { notFound } from 'next/navigation';
import { pageUser } from '@/lib/auth';
import { db } from '@/lib/db';
import { OrderView } from '@/components/order-view';
export default async function Page({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ created?: string }>;
}) {
  const user = await pageUser();
  const { id } = await params;
  const q = await searchParams;
  const order = await db.order.findFirst({
    where: { id, userId: user.id },
    include: { items: true, events: { orderBy: { createdAt: 'asc' } } },
  });
  if (!order) notFound();
  return (
    <>
      {q.created && (
        <div className="alert success" role="status">
          Đặt hàng thành công! Cảm ơn bạn đã chọn KH Fashion.
        </div>
      )}
      <OrderView order={order} />
    </>
  );
}
