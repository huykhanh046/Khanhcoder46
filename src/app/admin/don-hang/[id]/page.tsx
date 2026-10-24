import { pageUser } from '@/lib/auth';
import { notFound } from 'next/navigation';
import { db } from '@/lib/db';
import { OrderView } from '@/components/order-view';
export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  await pageUser(true);
  const { id } = await params;
  const order = await db.order.findUnique({
    where: { id },
    include: { items: true, events: { orderBy: { createdAt: 'asc' } } },
  });
  if (!order) notFound();
  return <OrderView order={order} admin />;
}
