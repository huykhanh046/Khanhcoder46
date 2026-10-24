import { redirect } from 'next/navigation';
import { currentUser } from '@/lib/auth';
import { db } from '@/lib/db';
import { Checkout } from '@/components/checkout';
export const metadata = { title: 'Thanh toán' };
export default async function Page() {
  const user = await currentUser();
  if (!user) redirect('/dang-nhap?next=/thanh-toan');
  const addresses = await db.address.findMany({
    where: { userId: user.id },
    orderBy: { createdAt: 'desc' },
  });
  return (
    <Checkout
      addresses={addresses}
      vnpay={!!(process.env.VNPAY_TMN_CODE && process.env.VNPAY_HASH_SECRET)}
    />
  );
}
