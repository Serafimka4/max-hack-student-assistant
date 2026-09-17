import { ReportSheet, TabBar, Toast } from './components/ui'
import { Career } from './screens/Career'
import { Help } from './screens/Help'
import { Home } from './screens/Home'
import { OfferDetail } from './screens/OfferDetail'
import { Schedule } from './screens/Schedule'
import { StoreProvider, useStore } from './store'
import './screens.css'

function Router() {
  const { tab, offerId } = useStore()
  if (offerId) return <OfferDetail id={offerId} />
  switch (tab) {
    case 'home': return <Home />
    case 'schedule': return <Schedule />
    case 'career': return <Career />
    case 'help': return <Help />
  }
}

export default function App() {
  return (
    <StoreProvider>
      <div className="app">
        <Router />
        <TabBar />
        <ReportSheet />
        <Toast />
      </div>
    </StoreProvider>
  )
}
