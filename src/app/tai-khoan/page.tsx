import { pageUser } from '@/lib/auth';
import { ProfileForm } from '@/components/account';
export default async function Page() {
  const user = await pageUser();
  return (
    <section className="panel">
      <h2>Thông tin cá nhân</h2>
      <ProfileForm name={user.name} email={user.email} />
    </section>
  );
}
