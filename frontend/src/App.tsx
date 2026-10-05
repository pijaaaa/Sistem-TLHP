import { Routes, Route } from 'react-router-dom'
import { AppLayout } from '@/layouts/AppLayout'
import HomePage from '@/pages/HomePage'
import DevComponentsPage from '@/pages/DevComponentsPage'

function App() {
  return (
    <Routes>
      <Route path="/" element={<AppLayout />}>
        <Route index element={<HomePage />} />
        <Route path="dev/components" element={<DevComponentsPage />} />
        {/* Routes tambahan akan ditambahkan di milestone berikutnya */}
      </Route>
    </Routes>
  )
}

export default App
