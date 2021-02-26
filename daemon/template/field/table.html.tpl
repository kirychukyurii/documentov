<table class="table table-bordered">
  <thead>
    <tr>
    {{ range .Headers }}
      <th>{{ . }}</th>
    {{ end }}  
    </tr>
  </thead>
  <tbody>
    {{ range .Rows }}
    <tr>
      {{ range . }}
      <td>{{ . }}</td>
      {{ end }}
    </tr>
    {{ end }}
  </tbody>  
</table>
