import { useEffect, useState } from 'react'
import './App.css'
import TicketDetail from './pages/TicketDetail'
import CreateTicket from './pages/CreateTicket'


const API_BASE_URL = 'http://localhost/support-desk/backend/web/v1'

function App() {
  const [token, setToken] = useState(() => {
    return localStorage.getItem('supportDeskToken')
  })

  const [user, setUser] = useState(() => {
    const savedUser = localStorage.getItem('supportDeskUser')

    try {
      return savedUser ? JSON.parse(savedUser) : null
    } catch {
      return null
    }
  })

  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')

  const [tickets, setTickets] = useState([])
  const [loading, setLoading] = useState(false)

  const [aiSearchQuery, setAiSearchQuery] = useState('')
  const [aiSearchLoading, setAiSearchLoading] = useState(false)
  const [aiSearchError, setAiSearchError] = useState('')
  const [aiSearchActive, setAiSearchActive] = useState(false)

  const [ticketsLoading, setTicketsLoading] = useState(false)
  const [error, setError] = useState('')
  const [currentPage, setCurrentPage] = useState('dashboard')
  const [selectedTicketId, setSelectedTicketId] = useState(null)

  /*
   * TICKET FILTERS
   */
  const [ticketSearch, setTicketSearch] = useState('')
  const [ticketStatusFilter, setTicketStatusFilter] = useState('')
  const [ticketPriorityFilter, setTicketPriorityFilter] = useState('')

  const [currentTicketPage, setCurrentTicketPage] = useState(1)
  const ticketsPerPage = 10

  /*
   * LOGIN
   */
  const handleLogin = async (event) => {
    event.preventDefault()

    setError('')
    setLoading(true)

    try {
      const response = await fetch(`${API_BASE_URL}/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          email,
          password,
        }),
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(
          data.message || 'Login failed. Please check your credentials.'
        )
      }

      /*
       * Save authentication information.
       */
      localStorage.setItem('supportDeskToken', data.token)
      localStorage.setItem('supportDeskUser', JSON.stringify(data.user))

      setToken(data.token)
      setUser(data.user)

      setError('')
    } catch (err) {
      setError(err.message || 'Unable to connect to the server.')
    } finally {
      setLoading(false)
    }
  }

  /*
   * LOGOUT
   */
  const handleLogout = () => {
    localStorage.removeItem('supportDeskToken')
    localStorage.removeItem('supportDeskUser')

    setToken(null)
    setUser(null)
    setTickets([])
    setError('')

    /*
     * Also clear filters after logout.
     */
    setTicketSearch('')
    setTicketStatusFilter('')
    setTicketPriorityFilter('')
  }

  /*
   * LOAD TICKETS
   */
  const loadTickets = async () => {
    if (!token) {
      return
    }

    setTicketsLoading(true)
    setError('')

    try {
      const response = await fetch(`${API_BASE_URL}/ticket`, {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(
          data.message || 'Unable to load tickets.'
        )
      }

      /*
       * Yii may return either:
       *
       * 1. An array
       * 2. An object containing items
       */
      if (Array.isArray(data)) {
        setTickets(data)
      } else if (Array.isArray(data.items)) {
        setTickets(data.items)
      } else {
        setTickets([])
      }
    } catch (err) {
      setError(err.message || 'Unable to load tickets.')
    } finally {
      setTicketsLoading(false)
    }
  }


  const handleAiSearch = async (event) => {
    event.preventDefault()

    if (!aiSearchQuery.trim()) {
      setAiSearchError('Please enter a search query.')
      return
    }

    setAiSearchLoading(true)
    setAiSearchError('')

    try {
      const response = await fetch(
        `${API_BASE_URL}/ticket/ai-search`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({
            query: aiSearchQuery.trim(),
          }),
        }
      )

      const data = await response.json()

      console.log('AI search response:', response.status, data)

      if (!response.ok) {
        throw new Error(
          data.message ||
          data.name ||
          `AI search failed. HTTP ${response.status}`
        )
      }

      setTickets(data.tickets || [])
      setAiSearchActive(true)

    } catch (err) {
      console.error('AI search error:', err)

      setAiSearchError(
        err.message || 'AI search failed.'
      )

    } finally {
      setAiSearchLoading(false)
    }
  }

  const clearAiSearch = () => {
    setAiSearchQuery('')
    setAiSearchError('')
    setAiSearchActive(false)
    loadTickets()
  }

  /*
  * LOAD TICKETS WHEN TOKEN EXISTS
  */
  useEffect(() => {
    if (token) {
      loadTickets()
    }
  }, [token])

  /*
  * FILTER TICKETS
  *
  * Search:
  * - Ticket ID
  * - Subject
  *
  * Filters:
  * - Status
  * - Priority
  */
  const filteredTickets = tickets.filter((ticket) => {
    const search = ticketSearch.trim().toLowerCase()

    const ticketId = String(ticket.id ?? '').toLowerCase()

    const subject = String(
      ticket.subject ?? ''
    ).toLowerCase()

    const status = String(
      ticket.status ?? ''
    ).toLowerCase()

    const priority = String(
      ticket.priority ?? ''
    ).toLowerCase()

    /*
    * Search by ticket ID or subject.
    */
    const matchesSearch =
      search === '' ||
      ticketId.includes(search) ||
      subject.includes(search)

    /*
    * Status filter.
    */
    const matchesStatus =
      ticketStatusFilter === '' ||
      status === ticketStatusFilter.toLowerCase()

    /*
    * Priority filter.
    */
    const matchesPriority =
      ticketPriorityFilter === '' ||
      priority === ticketPriorityFilter.toLowerCase()

    return (
      matchesSearch &&
      matchesStatus &&
      matchesPriority
    )
  })


  const totalTicketPages = Math.ceil(
    filteredTickets.length / ticketsPerPage
  )

  const paginationStartIndex =
    (currentTicketPage - 1) * ticketsPerPage

  const paginatedTickets = filteredTickets.slice(
    paginationStartIndex,
    paginationStartIndex + ticketsPerPage
  )


  /*
   * CLEAR ALL FILTERS
   */
  const clearFilters = () => {
    setTicketSearch('')
    setTicketStatusFilter('')
    setTicketPriorityFilter('')
    setCurrentTicketPage(1)
  }

  const openTicket = (ticketId) => {
    setSelectedTicketId(ticketId)
    setCurrentPage('ticket-detail')
  }

  /*
   * LOGIN SCREEN
   */
  if (!token) {
    return (
      <div className="app login-page">

        <div className="login-card">

          <div className="brand">

            <div className="brand-icon">
              SD
            </div>

            <div>
              <div className="brand-name">
                Support Desk
              </div>

              <div className="brand-subtitle">
                Ticket Management
              </div>
            </div>

          </div>


          <div className="login-header">

            <h1>
              Welcome back
            </h1>

            <p>
              Sign in to manage your customer support tickets.
            </p>

          </div>


          {error && (
            <div className="error-message">
              {error}
            </div>
          )}


          <form onSubmit={handleLogin}>

            <div className="form-group">

              <label htmlFor="email">
                Email address
              </label>

              <input
                id="email"
                type="email"
                value={email}
                onChange={(event) =>
                  setEmail(event.target.value)
                }
                placeholder="Enter your email"
                required
                disabled={loading}
              />

            </div>


            <div className="form-group">

              <div className="password-label-row">

                <label htmlFor="password">
                  Password
                </label>

                <span className="agent-hint">
                  Agent account
                </span>

              </div>

              <input
                id="password"
                type="password"
                value={password}
                onChange={(event) =>
                  setPassword(event.target.value)
                }
                placeholder="Enter your password"
                required
                disabled={loading}
              />

            </div>


            <button
              type="submit"
              className="login-button"
              disabled={loading}
            >

              {loading ? (
                <>
                  <span className="spinner"></span>
                  Signing in...
                </>
              ) : (
                <>
                  Sign in
                  <span>→</span>
                </>
              )}

            </button>

          </form>


          <div className="connection-status">

            <span className="status-dot"></span>

            Support Desk API

          </div>

        </div>

      </div>
    )
  }


  /*
   * DASHBOARD
   */
  return (
    <div className="dashboard">

      {/* SIDEBAR */}

      <aside className="sidebar">

        <div className="sidebar-brand">

          <div className="brand-icon">
            SD
          </div>

          <div>

            <div className="sidebar-brand-name">
              Support Desk
            </div>

            <div className="sidebar-brand-subtitle">
              Admin Console
            </div>

          </div>

        </div>


        <div className="sidebar-section-title">
          WORKSPACE
        </div>


        <nav className="sidebar-nav">

          <button
            className={`nav-item ${
              currentPage === 'dashboard'
                ? 'active'
                : ''
            }`}
            onClick={() =>
              setCurrentPage('dashboard')
            }
          >
            <span>▣</span>
            Dashboard
          </button>


          <button
            className={`nav-item ${
              currentPage === 'tickets'
                ? 'active'
                : ''
            }`}
            onClick={() =>
              setCurrentPage('tickets')
            }
          >
            <span>▤</span>

            Tickets

            <span className="nav-count">
              {tickets.length}
            </span>

          </button>


          <button
            className="nav-item"
            onClick={loadTickets}
          >
            <span>↻</span>
            Refresh
          </button>

        </nav>


        <div className="sidebar-section-title">
          MANAGEMENT
        </div>


        <nav className="sidebar-nav">

          <button className="nav-item">
            <span>♙</span>
            Customers
          </button>


          <button className="nav-item">
            <span>▥</span>
            Reports
          </button>

        </nav>


        <div className="sidebar-bottom">

          <div className="sidebar-user">

            <div className="avatar">
              {user?.email?.charAt(0).toUpperCase() || 'A'}
            </div>


            <div className="sidebar-user-info">

              <strong>
                {user?.email || 'Agent'}
              </strong>

              <span>
                {user?.role || 'agent'}
              </span>

            </div>

          </div>


          <button
            className="logout-button"
            onClick={handleLogout}
          >
            Logout
          </button>

        </div>

      </aside>


      {/* MAIN */}

      <main className="main-content">


        {/* =====================================================
            DASHBOARD PAGE
            ===================================================== */}

        {currentPage === 'dashboard' && (
          <>

            <header className="topbar">

              <div>

                <span className="breadcrumb">
                  Workspace
                </span>

                <span className="breadcrumb-separator">
                  /
                </span>

                <strong>
                  Dashboard
                </strong>

              </div>


              <div className="topbar-user">

                <button
                  className="refresh-icon"
                  onClick={loadTickets}
                  title="Refresh tickets"
                >
                  ↻
                </button>


                <div className="top-avatar">
                  {user?.email?.charAt(0).toUpperCase() || 'A'}
                </div>


                <div className="top-user-info">

                  <strong>
                    {user?.email || 'Agent'}
                  </strong>

                  <span>
                    {user?.role || 'agent'}
                  </span>

                </div>

              </div>

            </header>


            <section className="content">

              <div className="welcome-row">

                <div>

                  <div className="section-label">
                    OVERVIEW
                  </div>

                  <h1>
                    Good to see you 👋
                  </h1>

                  <p>
                    Here's what's happening with your support desk.
                  </p>

                </div>


                <button
                  className="refresh-button"
                  onClick={loadTickets}
                  disabled={ticketsLoading}
                >
                  {ticketsLoading
                    ? 'Loading...'
                    : '↻ Refresh'}
                </button>

              </div>


              {error && (
                <div className="dashboard-error">
                  {error}
                </div>
              )}


              {/* STAT CARDS */}

              <div className="stats-grid">

                <div className="stat-card">

                  <div className="stat-label">
                    TOTAL TICKETS
                  </div>

                  <div className="stat-number">
                    {tickets.length}
                  </div>

                  <div className="stat-description">
                    All support tickets
                  </div>

                </div>


                <div className="stat-card">

                  <div className="stat-label">
                    OPEN
                  </div>

                  <div className="stat-number">
                    {
                      tickets.filter(
                        (ticket) =>
                          String(ticket.status ?? '').toLowerCase() ===
                          'open'
                      ).length
                    }
                  </div>

                  <div className="stat-description">
                    Awaiting attention
                  </div>

                </div>


                <div className="stat-card">

                  <div className="stat-label">
                    PENDING
                  </div>

                  <div className="stat-number">
                    {
                      tickets.filter((ticket) => {
                        const status =
                          String(
                            ticket.status ?? ''
                          ).toLowerCase()

                        return status === 'pending'
                      }).length
                    }
                  </div>

                  <div className="stat-description">
                    Currently being handled
                  </div>

                </div>


                <div className="stat-card">

                  <div className="stat-label">
                    RESOLVED
                  </div>

                  <div className="stat-number">
                    {
                      tickets.filter(
                        (ticket) =>
                          String(ticket.status ?? '').toLowerCase() ===
                          'resolved'
                      ).length
                    }
                  </div>

                  <div className="stat-description">
                    Successfully completed
                  </div>

                </div>

              </div>


              {/* TICKETS CARD */}

              <div className="tickets-card">


                {/* FILTERS */}

                <div className="ticket-filters">

                  <input
                    type="text"
                    className="ticket-search"
                    placeholder="Search by ticket ID or subject..."
                    value={ticketSearch}
                    onChange={(event) => {
                      setTicketSearch(event.target.value)
                      setCurrentTicketPage(1)
                    }}
                  />


                  <select
                    className="ticket-filter"
                    value={ticketStatusFilter}
                    onChange={(event) => {
                      setTicketStatusFilter(event.target.value)
                      setCurrentTicketPage(1)
                    }}
                  >

                    <option value="">
                      All statuses
                    </option>

                    <option value="open">
                      Open
                    </option>

                    <option value="pending">
                      Pending
                    </option>

                    <option value="resolved">
                      Resolved
                    </option>

                    <option value="closed">
                      Closed
                    </option>

                  </select>


                  <select
                    className="ticket-filter"
                    value={ticketPriorityFilter}
                    onChange={(event) => {
                      setTicketPriorityFilter(event.target.value)
                      setCurrentTicketPage(1)
                    }}
                  >

                    <option value="">
                      All priorities
                    </option>

                    <option value="low">
                      Low
                    </option>

                    <option value="medium">
                      Medium
                    </option>

                    <option value="high">
                      High
                    </option>

                    <option value="critical">
                      Critical
                    </option>

                  </select>


                  <button
                    className="clear-filters-button"
                    onClick={clearFilters}
                  >
                    Clear
                  </button>

                </div>


                {/* HEADER */}

                <div className="tickets-header">

                  <div>

                    <h2>
                      Recent tickets
                    </h2>

                    <p>
                      {filteredTickets.length} of{' '}
                      {tickets.length} ticket(s)
                    </p>

                  </div>


                  <button
                    className="view-all-button"
                    onClick={() =>
                      setCurrentPage('tickets')
                    }
                  >
                    View all →
                  </button>

                </div>


                {/* TICKET CONTENT */}

                {ticketsLoading ? (

                  <div className="empty-state">

                    <div className="loading-circle"></div>

                    <p>
                      Loading tickets...
                    </p>

                  </div>

                ) : filteredTickets.length === 0 ? (

                  <div className="empty-state">

                    <div className="empty-icon">
                      ✓
                    </div>

                    <h3>
                      No matching tickets
                    </h3>

                    <p>
                      Try changing your search or filters.
                    </p>

                  </div>

                ) : (

                  <>

                  <div className="ticket-list">

                      {paginatedTickets.map((ticket) => (

                        <div
                          className="ticket-row"
                          key={ticket.id}
                          onClick={() => openTicket(ticket.id)}
                          style={{ cursor: 'pointer' }}
                        >

                          <div className="ticket-id">
                            #{ticket.id}
                          </div>

                          <div className="ticket-subject">
                            {ticket.subject || 'No subject'}
                          </div>

                          <div className="ticket-status">
                            {ticket.status || 'Unknown'}
                          </div>

                          <div
                            className={`ticket-sla ${
                              ticket.is_sla_breached
                                ? 'ticket-sla-breached'
                                : ticket.sla_status === 'within_sla'
                                  ? 'ticket-sla-within'
                                  : ticket.sla_status === 'resolved'
                                    ? 'ticket-sla-resolved'
                                    : 'ticket-sla-none'
                            }`}
                          >
                            {ticket.is_sla_breached
                              ? 'SLA Breached'
                              : ticket.sla_status === 'within_sla'
                                ? 'Within SLA'
                                : ticket.sla_status === 'resolved'
                                  ? 'Resolved'
                                  : '-'}
                          </div>

                          
                          <div
                            className={`ticket-escalation ${
                              ticket.escalation_status === 'escalated'
                                ? 'ticket-escalation-danger'
                                : ticket.escalation_status === 'at_risk'
                                  ? 'ticket-escalation-warning'
                                  : 'ticket-escalation-none'
                            }`}
                          >
                            {ticket.escalation_status === 'escalated'
                              ? 'Escalated'
                              : ticket.escalation_status === 'at_risk'
                                ? 'At Risk'
                                : ''}
                          </div>


                          <div className="ticket-date">
                            {ticket.created_at || '-'}
                          </div>

                        </div>

                      ))}

                    </div>


                    {totalTicketPages > 1 && (
                      <div className="pagination">

                        <button
                          className="pagination-button"
                          disabled={currentTicketPage === 1}
                          onClick={() =>
                            setCurrentTicketPage(
                              currentTicketPage - 1
                            )
                          }
                        >
                          ← Previous
                        </button>


                        <span className="pagination-info">
                          Page {currentTicketPage} of {totalTicketPages}
                        </span>


                        <button
                          className="pagination-button"
                          disabled={
                            currentTicketPage === totalTicketPages
                          }
                          onClick={() =>
                            setCurrentTicketPage(
                              currentTicketPage + 1
                            )
                          }
                        >
                          Next →
                        </button>

                      </div>
                    )}

                  </>

                )}

              </div>

            </section>

          </>
        )}


        {/* =====================================================
            TICKETS PAGE
            ===================================================== */}

        {currentPage === 'tickets' && (

          <section className="content">

            <div className="welcome-row">
              <div>
                <div className="section-label">WORKSPACE</div>
                <h1>Tickets</h1>
                <p>Manage and track support tickets.</p>
              </div>

              <div className="ticket-page-actions">
                <button
                  type="button"
                  className="refresh-button"
                  onClick={() => setCurrentPage('create-ticket')}
                >
                  + Create Ticket
                </button>

                <button
                  type="button"
                  className="refresh-button"
                  onClick={loadTickets}
                >
                  Refresh
                </button>
              </div>
            </div>


            <div className="ai-search-box">
              <div className="section-label">AI SEARCH</div>

              <form onSubmit={handleAiSearch}>
                <div className="ai-search-row">
                  <input
                    type="text"
                    value={aiSearchQuery}
                    onChange={(event) =>
                      setAiSearchQuery(event.target.value)
                    }
                    placeholder="Try: show me critical technical tickets"
                    disabled={aiSearchLoading}
                  />

                  <button
                    type="submit"
                    className="refresh-button"
                    disabled={aiSearchLoading}
                  >
                    {aiSearchLoading ? 'Searching...' : 'AI Search'}
                  </button>

                  {aiSearchActive && (
                    <button
                      type="button"
                      className="refresh-button"
                      onClick={clearAiSearch}
                      disabled={aiSearchLoading}
                    >
                      Clear
                    </button>
                  )}
                </div>
              </form>

              {aiSearchError && (
                <div className="dashboard-error">
                  {aiSearchError}
                </div>
              )}
            </div>



            <div className="tickets-card">


              {/* FILTERS */}

              <div className="ticket-filters">

                <input
                  type="text"
                  className="ticket-search"
                  placeholder="Search by ticket ID or subject..."
                  value={ticketSearch}
                  onChange={(event) => {
                    setTicketSearch(event.target.value)
                    setCurrentTicketPage(1)
                  }}
                />


                <select
                  className="ticket-filter"
                  value={ticketStatusFilter}
                  onChange={(event) => {
                    setTicketStatusFilter(event.target.value)
                    setCurrentTicketPage(1)
                  }}
                >

                  <option value="">
                    All statuses
                  </option>

                  <option value="open">
                    Open
                  </option>

                  <option value="pending">
                    Pending
                  </option>

                  <option value="resolved">
                    Resolved
                  </option>

                  <option value="closed">
                    Closed
                  </option>

                </select>


                <select
                  className="ticket-filter"
                  value={ticketPriorityFilter}
                  onChange={(event) => {
                    setTicketPriorityFilter(event.target.value)
                    setCurrentTicketPage(1)
                  }}
                >

                  <option value="">
                    All priorities
                  </option>

                  <option value="low">
                    Low
                  </option>

                  <option value="medium">
                    Medium
                  </option>

                  <option value="high">
                    High
                  </option>

                  <option value="critical">
                    Critical
                  </option>

                </select>


                <button
                  className="clear-filters-button"
                  onClick={clearFilters}
                >
                  Clear
                </button>

              </div>


              {/* HEADER */}

              <div className="tickets-header">

                <div>

                  <h2>
                    All tickets
                  </h2>

                  <p>
                    {filteredTickets.length} of{' '}
                    {tickets.length} ticket(s)
                  </p>

                </div>

              </div>


              {/* TICKETS */}

              {ticketsLoading ? (

                <div className="empty-state">

                  <div className="loading-circle"></div>

                  <p>
                    Loading tickets...
                  </p>

                </div>

              ) : filteredTickets.length === 0 ? (

                <div className="empty-state">

                  <div className="empty-icon">
                    ✓
                  </div>

                  <h3>
                    No matching tickets
                  </h3>

                  <p>
                    Try changing your search or filters.
                  </p>

                </div>

              ) : (

                <div className="ticket-list">

                  {paginatedTickets.map((ticket) => (

                    <div
                      className="ticket-row"
                      key={ticket.id}
                      onClick={() => openTicket(ticket.id)}
                      style={{ cursor: 'pointer' }}
                    >

                      <div className="ticket-id">
                        #{ticket.id}
                      </div>


                      <div className="ticket-subject">
                        {ticket.subject || 'No subject'}
                      </div>


                      <div className="ticket-status">
                        {ticket.status || 'Unknown'}
                      </div>

                      <div
                        className={`ticket-sla ${
                          ticket.is_sla_breached
                            ? 'ticket-sla-breached'
                            : ticket.sla_status === 'within_sla'
                              ? 'ticket-sla-within'
                              : ticket.sla_status === 'resolved'
                                ? 'ticket-sla-resolved'
                                : 'ticket-sla-none'
                        }`}
                      >
                        {ticket.is_sla_breached
                          ? 'SLA Breached'
                          : ticket.sla_status === 'within_sla'
                            ? 'Within SLA'
                            : ticket.sla_status === 'resolved'
                              ? 'Resolved'
                              : '-'}
                      </div>

                      
                      <div
                        className={`ticket-escalation ${
                          ticket.escalation_status === 'escalated'
                            ? 'ticket-escalation-danger'
                            : ticket.escalation_status === 'at_risk'
                              ? 'ticket-escalation-warning'
                              : 'ticket-escalation-none'
                        }`}
                      >
                        {ticket.escalation_status === 'escalated'
                          ? 'Escalated'
                          : ticket.escalation_status === 'at_risk'
                            ? 'At Risk'
                            : ''}
                      </div>


                      <div className="ticket-date">
                        {ticket.created_at || '-'}
                      </div>

                    </div>

                  ))}

                </div>

              )}

            </div>

          </section>

        )}


        {currentPage === 'ticket-detail' && selectedTicketId && (
          <TicketDetail
            ticketId={selectedTicketId}
            token={token}
            onBack={() => setCurrentPage('tickets')}
          />
        )}


        {currentPage === 'create-ticket' && (
          <CreateTicket
            token={token}
            onBack={() => setCurrentPage('tickets')}
            onCreated={() => {
              setCurrentPage('tickets')
              loadTickets()
            }}
          />
        )}

      </main>

    </div>
  )
}

export default App