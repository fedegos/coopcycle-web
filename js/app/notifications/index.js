import React from 'react'
import { render } from 'react-dom'
import NotificationList from './NotificationList'

window.CoopCycle = window.CoopCycle || {}

window.CoopCycle.NotificationsListener = ($popover, username, options) => {

  if ($popover.length === 0) {
    return
  }

  let template = document.createElement('script')
  template.type = 'text/template'
  document.body.appendChild(template)

  let component

  const initPopover = () => {

    $popover.popover({
      placement: 'bottom',
      container: 'body',
      html: true,
      template: `<div class="popover" role="tooltip">
        <div class="arrow"></div>
        <div class="popover-content nopadding"></div>
      </div>`,
    })

    $popover.on('shown.bs.popover', () => {
      const notifications = component.toArray().map(notification => notification.id)
      $.ajax(options.markAsReadURL, {
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(notifications),
      })
    })
  }

  const setPopoverContent = () => {
    $popover.attr('data-content', template.innerHTML)
  }

  const socket = io('//' + window.location.hostname, { path: '/tracking/socket.io' })

  socket.on(`user:${username}:notifications`, notification => component.unshift(notification))
  socket.on(`user:${username}:notifications:count`, count => options.elements.count.innerHTML = count)

  $.getJSON(options.unreadCountURL)
    .then(count => options.elements.count.innerHTML = count)

  $.getJSON(options.notificationsURL, { format: 'json' })
    .then(notifications => {
      component = render(
        <NotificationList
          notifications={ notifications }
          url={ options.notificationsURL }
          emptyMessage={ options.emptyMessage }
          onUpdate={ () => setPopoverContent() } />,
        template,
        () => {
          setPopoverContent()
          initPopover()
        }
      )
    })
}
